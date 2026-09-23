<?php
/**
 * Dynamic Production Blog Engine for Dhanaji Prajapati SEO
 * Powered by PHP + MySQL/MariaDB with PDO Prepared Statements
 * Handles both /blog/ archive and /blog/article-slug/ dynamic routing
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ai-login/auth.php';

$pdo = get_db();

// Determine if this is a single post or the blog archive
$slug = trim($_GET['slug'] ?? '');
if (empty($slug) && isset($_SERVER['REQUEST_URI'])) {
    $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#^/blog/([a-zA-Z0-9_-]+)/?$#', $uriPath, $matches)) {
        if ($matches[1] !== 'ai-login' && $matches[1] !== 'index.php') {
            $slug = $matches[1];
        }
    }
}

// =========================================================================
// SINGLE ARTICLE VIEW (/blog/article-slug/)
// =========================================================================
if (!empty($slug)) {
    // Query post by slug
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.display_name AS author_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.slug = ?
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();

    $isAdmin = is_admin_logged_in();

    // If post does not exist or is not published (and viewer is not admin previewing)
    if (!$post || ($post['status'] !== 'published' && !$isAdmin)) {
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Article Not Found — Dhanaji Prajapati</title>
          <meta name="robots" content="noindex, nofollow">
          <script src="https://cdn.tailwindcss.com"></script>
          <link rel="stylesheet" href="/assets/css/styles.css">
        </head>
        <body class="bg-white text-slate-900 min-h-screen flex flex-col antialiased">
          <header class="border-b border-slate-200 py-4 px-6 flex items-center justify-between">
            <a href="/" class="text-base font-bold text-slate-900">Dhanaji Prajapati</a>
            <a href="/blog/" class="text-sm font-semibold text-blue-700">&larr; Back to Blog</a>
          </header>
          <main class="flex-1 flex items-center justify-center p-6 text-center">
            <div class="max-w-md">
              <span class="text-4xl font-extrabold text-blue-700 font-mono">404</span>
              <h1 class="text-2xl font-bold text-slate-900 mt-2">Article Not Found</h1>
              <p class="text-sm text-slate-600 mt-2">The requested blog publication has moved, is unpublished, or does not exist.</p>
              <div class="mt-6">
                <a href="/blog/" class="px-5 py-2.5 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-blue-700 transition-colors">
                  Explore Blog Archive
                </a>
              </div>
            </div>
          </main>
        </body>
        </html>
        <?php
        exit;
    }

    // Load Tags for this article
    $tagStmt = $pdo->prepare("
        SELECT t.name, t.slug
        FROM tags t
        INNER JOIN post_tags pt ON t.id = pt.tag_id
        WHERE pt.post_id = ?
        ORDER BY t.name ASC
    ");
    $tagStmt->execute([$post['id']]);
    $tags = $tagStmt->fetchAll();

    // Load Related Articles from MySQL
    $relatedStmt = $pdo->prepare("
        SELECT title, slug, excerpt, reading_time, publish_date
        FROM posts
        WHERE status = 'published' AND id != ? AND (category_id = ? OR category_id IS NOT NULL)
        ORDER BY publish_date DESC
        LIMIT 3
    ");
    $relatedStmt->execute([$post['id'], $post['category_id'] ?? 0]);
    $relatedPosts = $relatedStmt->fetchAll();

    // SEO Variables
    $metaTitle = !empty($post['seo_title']) ? $post['seo_title'] : ($post['title'] . ' | Dhanaji Prajapati');
    $metaDesc = !empty($post['meta_description']) ? $post['meta_description'] : ($post['excerpt'] ?? 'Technical SEO analysis by Dhanaji Prajapati.');
    $canonicalUrl = !empty($post['canonical_url']) ? $post['canonical_url'] : ('https://dhanajiprajapati.com/blog/' . $post['slug'] . '/');
    $robotsDirective = !empty($post['robots']) ? $post['robots'] : 'index, follow';
    $ogTitle = !empty($post['og_title']) ? $post['og_title'] : $post['title'];
    $ogDesc = !empty($post['og_description']) ? $post['og_description'] : $metaDesc;
    $ogImage = !empty($post['og_image']) ? $post['og_image'] : (!empty($post['featured_image']) ? $post['featured_image'] : 'https://dhanajiprajapati.com/assets/images/logo.svg');
    $readingTime = (int)$post['reading_time'] ?: 5;
    $publishIsoDate = date('c', strtotime($post['publish_date']));
    $modifiedIsoDate = date('c', strtotime($post['updated_at']));
    $publishDisplayDate = date('F j, Y', strtotime($post['publish_date']));
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?= e($metaTitle) ?></title>
      <meta name="description" content="<?= e($metaDesc) ?>">
      <link rel="canonical" href="<?= e($canonicalUrl) ?>">
      <meta name="robots" content="<?= e($robotsDirective) ?>">

      <!-- Open Graph / Facebook / LinkedIn -->
      <meta property="og:type" content="article">
      <meta property="og:url" content="<?= e($canonicalUrl) ?>">
      <meta property="og:title" content="<?= e($ogTitle) ?>">
      <meta property="og:description" content="<?= e($ogDesc) ?>">
      <meta property="og:image" content="<?= e($ogImage) ?>">
      <meta property="article:published_time" content="<?= $publishIsoDate ?>">
      <meta property="article:modified_time" content="<?= $modifiedIsoDate ?>">
      <meta property="article:author" content="https://dhanajiprajapati.com/about/">
      <?php if (!empty($post['category_name'])): ?>
        <meta property="article:section" content="<?= e($post['category_name']) ?>">
      <?php endif; ?>

      <!-- Twitter Cards -->
      <meta name="twitter:card" content="summary_large_image">
      <meta name="twitter:title" content="<?= e($ogTitle) ?>">
      <meta name="twitter:description" content="<?= e($ogDesc) ?>">
      <meta name="twitter:image" content="<?= e($ogImage) ?>">

      <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
      <script src="https://cdn.tailwindcss.com"></script>
      <link rel="stylesheet" href="/assets/css/styles.css">

      <!-- Article Structured Data (Schema.org / JSON-LD) -->
      <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": <?= json_encode($post['title']) ?>,
        "description": <?= json_encode($metaDesc) ?>,
        "url": <?= json_encode($canonicalUrl) ?>,
        "datePublished": <?= json_encode($publishIsoDate) ?>,
        "dateModified": <?= json_encode($modifiedIsoDate) ?>,
        "author": {
          "@type": "Person",
          "name": <?= json_encode($post['author_name'] ?: 'Dhanaji Prajapati') ?>,
          "url": "https://dhanajiprajapati.com/about/"
        },
        "publisher": {
          "@type": "Person",
          "name": "Dhanaji Prajapati",
          "url": "https://dhanajiprajapati.com"
        },
        "mainEntityOfPage": {
          "@type": "WebPage",
          "@id": <?= json_encode($canonicalUrl) ?>
        }
      }
      </script>

      <!-- Breadcrumbs Structured Data -->
      <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
          {
            "@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "https://dhanajiprajapati.com/"
          },
          {
            "@type": "ListItem",
            "position": 2,
            "name": "Blog",
            "item": "https://dhanajiprajapati.com/blog/"
          },
          {
            "@type": "ListItem",
            "position": 3,
            "name": <?= json_encode($post['title']) ?>,
            "item": <?= json_encode($canonicalUrl) ?>
          }
        ]
      }
      </script>
    </head>
    <body class="bg-white text-slate-900 antialiased selection:bg-slate-900 selection:text-white flex flex-col min-h-screen">

      <!-- Admin Preview Bar if logged in -->
      <?php if ($isAdmin): ?>
        <div class="bg-blue-600 text-white text-xs py-2 px-4 flex items-center justify-between sticky top-0 z-50">
          <div class="flex items-center space-x-2">
            <span class="font-bold uppercase tracking-wider text-[10px] bg-blue-800 px-2 py-0.5 rounded">Admin Mode</span>
            <span>Status: <strong><?= e($post['status']) ?></strong></span>
          </div>
          <div class="flex items-center space-x-3 text-xs">
            <a href="/blog/ai-login/post-editor?id=<?= $post['id'] ?>" class="underline hover:text-blue-100">Edit in Admin &rarr;</a>
            <a href="/blog/ai-login/dashboard" class="underline hover:text-blue-100">Dashboard</a>
          </div>
        </div>
      <?php endif; ?>

      <!-- Top Announcement Bar -->
      <div class="bg-slate-900 text-slate-300 text-xs py-2 px-4 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
            <span class="font-medium text-slate-200">Available for Work</span>
            <span class="text-slate-500 hidden sm:inline">|</span>
            <span class="text-slate-400 hidden sm:inline">Direct consulting engagements &amp; agency white-label retainers</span>
          </div>
          <div class="flex items-center space-x-4">
            <a href="tel:+919724483959" class="text-slate-300 hover:text-white transition-colors flex items-center space-x-1">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
              <span class="font-medium">+91 97244 83959</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Header -->
      <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex items-center justify-between h-16">
            <a href="/" class="flex items-center space-x-3 group" aria-label="Dhanaji Prajapati — Independent SEO Consultant">
              <div class="w-10 h-10 rounded-lg bg-slate-900 flex items-center justify-center text-white shrink-0 group-hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              </div>
              <div>
                <span class="block text-lg font-bold tracking-tight text-slate-900 leading-none">Dhanaji Prajapati</span>
                <span class="block text-[10px] font-semibold tracking-wider text-slate-500 uppercase mt-1">Independent SEO Consultant</span>
              </div>
            </a>

            <nav class="hidden lg:flex items-center space-x-6 text-sm font-medium text-slate-600">
              <a href="/" class="hover:text-blue-700 transition-colors">Home</a>
              <a href="/seo-services/" class="hover:text-blue-700 transition-colors">SEO Services</a>
              <a href="/seo-training/" class="hover:text-blue-700 transition-colors">SEO Training</a>
              <a href="/white-label-seo/" class="hover:text-blue-700 transition-colors">White Label SEO</a>
              <a href="/pricing/" class="hover:text-blue-700 transition-colors">Pricing</a>
              <a href="/about/" class="hover:text-blue-700 transition-colors">About</a>
              <a href="/portfolio/" class="hover:text-blue-700 transition-colors">Portfolio</a>
              <a href="/blog/" class="text-blue-700 font-semibold transition-colors">Blog</a>
              <a href="/contact/" class="hover:text-blue-700 transition-colors">Contact</a>
            </nav>

            <div class="flex items-center space-x-3">
              <a href="https://calendly.com/dhanajiprajapati12" target="_blank" rel="noopener noreferrer" class="hidden sm:inline-flex items-center justify-center px-4 py-2.5 rounded text-sm font-semibold text-white bg-slate-900 hover:bg-blue-700 transition-colors shadow-sm">
                Book a Strategy Call
              </a>
              <div class="flex items-center space-x-2 lg:hidden">
                <a href="tel:+919724483959" class="p-2 text-slate-700 hover:text-blue-700 hover:bg-slate-100 rounded transition-colors">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </a>
                <button id="mobile-menu-btn" type="button" class="p-2 text-slate-700 hover:text-slate-900 rounded" aria-label="Toggle menu" aria-expanded="false">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Mobile Drawer Menu -->
      <div id="mobile-menu-drawer" class="fixed inset-0 z-50 hidden lg:hidden" role="dialog" aria-modal="true">
        <div id="mobile-menu-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
        <div class="fixed inset-y-0 right-0 w-full max-w-xs bg-white shadow-xl flex flex-col z-10">
          <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <span class="text-sm font-bold text-slate-900">Navigation</span>
            <button id="mobile-menu-close" type="button" class="p-2 text-slate-600 hover:text-slate-900" aria-label="Close menu">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <a href="/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">Home</a>
            <a href="/seo-services/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">SEO Services</a>
            <div class="border-y border-slate-100 py-1">
              <button id="mobile-seo-training-btn" type="button" class="w-full flex items-center justify-between py-2 text-slate-800 font-medium hover:text-blue-700">
                <span>SEO Training</span>
                <svg id="mobile-seo-training-arrow" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <div id="mobile-seo-training-sub" class="hidden pl-4 pb-2 space-y-1">
                <a href="/seo-training/" class="block py-1.5 text-xs text-slate-600 hover:text-blue-700">SEO Training</a>
                <a href="/seo-training/one-to-one/" class="block py-1.5 text-xs text-slate-600 hover:text-blue-700">One-to-One SEO Training</a>
                <a href="/seo-training/group/" class="block py-1.5 text-xs text-slate-600 hover:text-blue-700">Group SEO Training</a>
              </div>
            </div>
            <a href="/white-label-seo/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">White Label SEO</a>
            <a href="/pricing/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">Pricing</a>
            <a href="/about/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">About</a>
            <a href="/portfolio/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">Portfolio</a>
            <a href="/blog/" class="block py-2 text-blue-700 font-semibold">Blog</a>
            <a href="/contact/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">Contact</a>
          </div>
          <div class="p-4 border-t border-slate-200">
            <a href="https://calendly.com/dhanajiprajapati12" target="_blank" rel="noopener noreferrer" class="w-full block text-center py-3 bg-slate-900 hover:bg-blue-700 text-white font-semibold rounded text-sm transition-colors">
              Book a Strategy Call
            </a>
          </div>
        </div>
      </div>

      <!-- Main Article Container -->
      <main class="flex-1 bg-white">
        <!-- Article Header Section -->
        <div class="bg-slate-900 text-white py-12 sm:py-16 border-b border-slate-800">
          <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-4 font-mono">
              <a href="/blog/" class="hover:text-white transition-colors">&larr; Blog Archive</a>
              <span>/</span>
              <?php if (!empty($post['category_name'])): ?>
                <span class="text-blue-400 font-semibold"><?= e($post['category_name']) ?></span>
                <span>/</span>
              <?php endif; ?>
              <span><?= $readingTime ?> min read</span>
            </div>

            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-white leading-tight">
              <?= e($post['title']) ?>
            </h1>

            <?php if (!empty($post['excerpt'])): ?>
              <p class="mt-4 text-base sm:text-lg text-slate-300 leading-relaxed font-normal">
                <?= e($post['excerpt']) ?>
              </p>
            <?php endif; ?>

            <div class="mt-6 pt-6 border-t border-slate-800 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-400">
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs">
                  DP
                </div>
                <div>
                  <span class="block font-semibold text-slate-200"><?= e($post['author_name'] ?: 'Dhanaji Prajapati') ?></span>
                  <span class="text-[11px] text-slate-400">Independent SEO Consultant</span>
                </div>
              </div>

              <div class="flex items-center space-x-4 font-mono text-[11px]">
                <span>Published: <?= $publishDisplayDate ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Featured Image if provided -->
        <?php if (!empty($post['featured_image'])): ?>
          <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6">
            <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['featured_image_alt'] ?: $post['title']) ?>"
              class="w-full h-auto max-h-[460px] object-cover rounded-2xl shadow-xl border border-slate-200">
          </div>
        <?php endif; ?>

        <!-- Article Body -->
        <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div class="prose prose-slate lg:prose-lg max-w-none prose-headings:font-bold prose-headings:text-slate-900 prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4 prose-h2:border-b prose-h2:border-slate-100 prose-h2:pb-2 prose-h3:text-xl prose-p:text-slate-700 prose-p:leading-relaxed prose-li:text-slate-700 prose-code:text-blue-700 prose-code:bg-blue-50 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-pre:bg-slate-950 prose-pre:text-slate-100 prose-pre:rounded-xl">
            <?= $post['content'] ?>
          </div>

          <!-- Tags -->
          <?php if (!empty($tags)): ?>
            <div class="mt-12 pt-6 border-t border-slate-200 flex flex-wrap items-center gap-2">
              <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mr-2">Topics:</span>
              <?php foreach ($tags as $tag): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                  #<?= e($tag['name']) ?>
                </span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- Author Bio Card -->
          <div class="mt-12 p-6 sm:p-8 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col sm:flex-row items-center sm:items-start gap-6">
            <div class="w-16 h-16 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xl shrink-0">
              DP
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900">About Dhanaji Prajapati</h3>
              <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                Dhanaji Prajapati is an independent Technical SEO Consultant based in India, specializing in enterprise crawl budget optimization, server access log forensics, Google Search Console big data pipelines, and white-label agency partnerships worldwide.
              </p>
              <div class="mt-3 flex items-center space-x-3 text-xs">
                <a href="https://calendly.com/dhanajiprajapati12" target="_blank" rel="noopener noreferrer" class="text-blue-700 font-semibold hover:underline">
                  Schedule Technical Consultation &rarr;
                </a>
              </div>
            </div>
          </div>

          <!-- Related Articles -->
          <?php if (!empty($relatedPosts)): ?>
            <div class="mt-16 pt-10 border-t border-slate-200">
              <h3 class="text-xl font-bold text-slate-900 tracking-tight mb-6">Related Technical Guides</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($relatedPosts as $rel): ?>
                  <a href="/blog/<?= e($rel['slug']) ?>/" class="block p-5 bg-white border border-slate-200 rounded-xl hover:border-blue-500 hover:shadow-md transition-all group">
                    <span class="text-[11px] font-mono text-slate-400 block mb-2"><?= (int)$rel['reading_time'] ?> min read</span>
                    <h4 class="text-sm font-bold text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2">
                      <?= e($rel['title']) ?>
                    </h4>
                    <p class="text-xs text-slate-600 mt-2 line-clamp-2">
                      <?= e($rel['excerpt']) ?>
                    </p>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </article>
      </main>

      <!-- Global Conversion Banner -->
      <section class="bg-blue-700 text-white py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center space-y-4">
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Need Technical SEO Execution on Your Website?</h2>
          <p class="text-sm sm:text-base text-blue-100 max-w-2xl mx-auto">
            Direct senior engineering engagement with zero agency account managers. Book a strategy session today.
          </p>
          <div class="pt-2">
            <a href="https://calendly.com/dhanajiprajapati12" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-slate-900 text-white font-bold text-sm hover:bg-slate-800 transition-colors shadow-lg">
              Book a Strategy Call Now &rarr;
            </a>
          </div>
        </div>
      </section>

      <!-- Footer -->
      <footer class="bg-slate-950 text-slate-400 py-12 px-4 sm:px-6 lg:px-8 border-t border-slate-900 text-xs">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
          <p>&copy; <?= date('Y') ?> Dhanaji Prajapati. All rights reserved. Independent SEO Consultant.</p>
          <div class="flex items-center space-x-4">
            <a href="/blog/" class="hover:text-white transition-colors">Technical SEO Blog</a>
            <span>&middot;</span>
            <a href="/contact/" class="hover:text-white transition-colors">Contact</a>
          </div>
        </div>
      </footer>

      <script src="/assets/js/main.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// =========================================================================
// PUBLIC BLOG ARCHIVE VIEW (/blog/)
// =========================================================================

// Search and category filters
$search = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');

$where = ["p.status = 'published'", "p.publish_date <= CURRENT_TIMESTAMP", "p.show_in_archive = 1"];
$params = [];

if ($search !== '') {
    $where[] = "(p.title LIKE ? OR p.excerpt LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($categorySlug !== '') {
    $where[] = "c.slug = ?";
    $params[] = $categorySlug;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Fetch published articles
$sql = "
    SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.featured_image_alt,
           p.reading_time, p.publish_date, p.is_featured,
           c.name AS category_name, c.slug AS category_slug,
           u.display_name AS author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    {$whereClause}
    ORDER BY p.is_featured DESC, p.publish_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$publishedPosts = $stmt->fetchAll();

// Fetch categories for filter tabs
$categories = $pdo->query("
    SELECT c.name, c.slug, COUNT(p.id) AS post_count
    FROM categories c
    INNER JOIN posts p ON c.id = p.category_id AND p.status = 'published' AND p.publish_date <= CURRENT_TIMESTAMP
    GROUP BY c.id, c.name, c.slug
    ORDER BY c.name ASC
")->fetchAll();

$totalPublishedCount = count($publishedPosts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Technical SEO Blog &amp; Engineering Insights &mdash; Dhanaji Prajapati</title>
  <meta name="description" content="In-depth technical SEO articles, crawl log analysis, Google Search Console indexing guides, and AI search visibility frameworks by Dhanaji Prajapati.">
  <link rel="canonical" href="https://dhanajiprajapati.com/blog/">
  
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://dhanajiprajapati.com/blog/">
  <meta property="og:title" content="Technical SEO Blog &mdash; Dhanaji Prajapati">
  <meta property="og:description" content="Pragmatic technical SEO guides, log analysis tutorials, and modern search engineering.">
  <meta property="og:image" content="https://dhanajiprajapati.com/assets/images/logo.svg">

  <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets/css/styles.css">

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Blog",
    "name": "Dhanaji Prajapati Technical SEO Blog",
    "description": "In-depth technical SEO articles, crawl log analysis, Google Search Console indexing guides, and AI search visibility frameworks.",
    "url": "https://dhanajiprajapati.com/blog/",
    "publisher": {
      "@type": "Person",
      "name": "Dhanaji Prajapati",
      "url": "https://dhanajiprajapati.com"
    }
  }
  </script>
</head>
<body class="bg-white text-slate-900 antialiased selection:bg-slate-900 selection:text-white flex flex-col min-h-screen">

  <!-- Top Announcement Bar -->
  <div class="bg-slate-900 text-slate-300 text-xs py-2 px-4 border-b border-slate-800">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
        <span class="font-medium text-slate-200">Available for Work</span>
        <span class="text-slate-500 hidden sm:inline">|</span>
        <span class="text-slate-400 hidden sm:inline">Direct consulting engagements &amp; agency white-label retainers</span>
      </div>
      <div class="flex items-center space-x-4">
        <a href="tel:+919724483959" class="text-slate-300 hover:text-white transition-colors flex items-center space-x-1">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          <span class="font-medium">+91 97244 83959</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <a href="/" class="flex items-center space-x-3 group" aria-label="Dhanaji Prajapati — Independent SEO Consultant">
          <div class="w-10 h-10 rounded-lg bg-slate-900 flex items-center justify-center text-white shrink-0 group-hover:bg-blue-700 transition-colors">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
          <div>
            <span class="block text-lg font-bold tracking-tight text-slate-900 leading-none">Dhanaji Prajapati</span>
            <span class="block text-[10px] font-semibold tracking-wider text-slate-500 uppercase mt-1">Independent SEO Consultant</span>
          </div>
        </a>

        <nav class="hidden lg:flex items-center space-x-6 text-sm font-medium text-slate-600">
          <a href="/" class="hover:text-blue-700 transition-colors">Home</a>
          <a href="/seo-services/" class="hover:text-blue-700 transition-colors">SEO Services</a>
          <a href="/seo-training/" class="hover:text-blue-700 transition-colors">SEO Training</a>
          <a href="/white-label-seo/" class="hover:text-blue-700 transition-colors">White Label SEO</a>
          <a href="/pricing/" class="hover:text-blue-700 transition-colors">Pricing</a>
          <a href="/about/" class="hover:text-blue-700 transition-colors">About</a>
          <a href="/portfolio/" class="hover:text-blue-700 transition-colors">Portfolio</a>
          <a href="/blog/" class="text-blue-700 font-semibold transition-colors">Blog</a>
          <a href="/contact/" class="hover:text-blue-700 transition-colors">Contact</a>
        </nav>

        <div class="flex items-center space-x-3">
          <a href="https://calendly.com/dhanajiprajapati12" target="_blank" rel="noopener noreferrer" class="hidden sm:inline-flex items-center justify-center px-4 py-2.5 rounded text-sm font-semibold text-white bg-slate-900 hover:bg-blue-700 transition-colors shadow-sm">
            Book a Strategy Call
          </a>
          <div class="flex items-center space-x-2 lg:hidden">
            <a href="tel:+919724483959" class="p-2 text-slate-700 hover:text-blue-700 hover:bg-slate-100 rounded transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </a>
            <button id="mobile-menu-btn" type="button" class="p-2 text-slate-700 hover:text-slate-900 rounded" aria-label="Toggle menu" aria-expanded="false">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Mobile Drawer Menu -->
  <div id="mobile-menu-drawer" class="fixed inset-0 z-50 hidden lg:hidden" role="dialog" aria-modal="true">
    <div id="mobile-menu-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
    <div class="fixed inset-y-0 right-0 w-full max-w-xs bg-white shadow-xl flex flex-col z-10">
      <div class="p-4 border-b border-slate-200 flex items-center justify-between">
        <span class="text-sm font-bold text-slate-900">Navigation</span>
        <button id="mobile-menu-close" type="button" class="p-2 text-slate-600 hover:text-slate-900" aria-label="Close menu">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="flex-1 overflow-y-auto p-4 space-y-3">
        <a href="/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">Home</a>
        <a href="/seo-services/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">SEO Services</a>
        <div class="border-y border-slate-100 py-1">
          <button id="mobile-seo-training-btn" type="button" class="w-full flex items-center justify-between py-2 text-slate-800 font-medium hover:text-blue-700">
            <span>SEO Training</span>
            <svg id="mobile-seo-training-arrow" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div id="mobile-seo-training-sub" class="hidden pl-4 pb-2 space-y-1">
            <a href="/seo-training/" class="block py-1.5 text-xs text-slate-600 hover:text-blue-700">SEO Training</a>
            <a href="/seo-training/one-to-one/" class="block py-1.5 text-xs text-slate-600 hover:text-blue-700">One-to-One SEO Training</a>
            <a href="/seo-training/group/" class="block py-1.5 text-xs text-slate-600 hover:text-blue-700">Group SEO Training</a>
          </div>
        </div>
        <a href="/white-label-seo/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">White Label SEO</a>
        <a href="/pricing/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">Pricing</a>
        <a href="/about/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">About</a>
        <a href="/portfolio/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">Portfolio</a>
        <a href="/blog/" class="block py-2 text-blue-700 font-semibold">Blog</a>
        <a href="/contact/" class="block py-2 text-slate-800 font-medium hover:text-blue-700">Contact</a>
      </div>
      <div class="p-4 border-t border-slate-200">
        <a href="https://calendly.com/dhanajiprajapati12" target="_blank" rel="noopener noreferrer" class="w-full block text-center py-3 bg-slate-900 hover:bg-blue-700 text-white font-semibold rounded text-sm transition-colors">
          Book a Strategy Call
        </a>
      </div>
    </div>
  </div>

  <main class="flex-1">
    <!-- Hero Section with Terminal -->
    <section class="bg-slate-950 text-white py-16 sm:py-24 relative overflow-hidden border-b border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          <div class="lg:col-span-7 space-y-6">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-blue-900/60 border border-blue-500/30 text-blue-300 text-xs font-mono">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>ENGINEERING_LOGS_ACTIVE</span>
            </div>
            
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight text-white">
              Technical SEO, Log Forensics &amp; Generative AI Search
            </h1>
            
            <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl font-normal">
              Zero fluff. Production-tested architectural deep dives, server access log parsing, indexing protocols, and algorithmic attribution frameworks written directly by an independent search consultant.
            </p>

            <!-- Search Bar in Hero -->
            <form method="GET" action="/blog/" class="pt-2 flex max-w-md">
              <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search technical guides..."
                class="w-full px-4 py-2.5 bg-slate-900 border border-slate-700 rounded-l-xl text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
              <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-r-xl transition-colors">
                Search
              </button>
            </form>
          </div>

          <!-- Search Terminal -->
          <div class="lg:col-span-5">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-2xl p-5 font-mono text-xs text-slate-300">
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800 text-slate-500 text-[11px]">
                <div class="flex items-center space-x-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                  <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                </div>
                <span>dhanaji@search-cluster:~</span>
              </div>
              <div class="space-y-2 text-[11px] leading-relaxed">
                <p class="text-emerald-400">$ cat /etc/seo/engine.conf</p>
                <p class="text-slate-400">&gt; Crawl Budget: Unconstrained</p>
                <p class="text-slate-400">&gt; Indexation Engine: MySQL / MariaDB Hostinger Cluster</p>
                <p class="text-slate-400">&gt; Entity Graph: RAG &amp; AI Citation Ready</p>
                <p class="text-slate-500 mt-2">$ query_database --status=published</p>
                <p class="text-blue-400 font-bold">&gt; Found <?= $totalPublishedCount ?> verified engineering guides</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Category Filter Bar -->
    <div class="border-b border-slate-200 bg-slate-50 sticky top-16 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center space-x-2 overflow-x-auto py-3 text-xs font-medium scrollbar-none">
          <a href="/blog/" class="px-3.5 py-1.5 rounded-full shrink-0 transition-colors <?= empty($categorySlug) ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200' ?>">
            All Topics
          </a>
          <?php foreach ($categories as $c): ?>
            <a href="/blog/?category=<?= e($c['slug']) ?>" class="px-3.5 py-1.5 rounded-full shrink-0 transition-colors <?= $categorySlug === $c['slug'] ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200' ?>">
              <?= e($c['name']) ?> (<?= $c['post_count'] ?>)
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Articles Grid Section -->
    <section class="py-16 sm:py-20 bg-slate-50/50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (!empty($search) || !empty($categorySlug)): ?>
          <div class="mb-8 flex items-center justify-between text-xs text-slate-500">
            <div>
              Showing results for: <strong class="text-slate-900"><?= e($search ?: $categorySlug) ?></strong> (<?= count($publishedPosts) ?> found)
            </div>
            <a href="/blog/" class="text-blue-700 font-semibold hover:underline">Clear Filters</a>
          </div>
        <?php endif; ?>

        <?php if (empty($publishedPosts)): ?>
          <div class="p-16 text-center bg-white border border-slate-200 rounded-2xl max-w-lg mx-auto">
            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h3 class="text-base font-bold text-slate-900">No Articles Found</h3>
            <p class="text-xs text-slate-500 mt-1">No published articles matched your selected criteria.</p>
            <a href="/blog/" class="inline-block mt-4 text-xs font-semibold text-blue-700 hover:underline">Return to All Posts</a>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($publishedPosts as $post): ?>
              <article class="bg-white border border-slate-200 rounded-2xl overflow-hidden hover:border-slate-300 hover:shadow-xl transition-all duration-200 flex flex-col justify-between group">
                <div class="p-6 sm:p-7">
                  <!-- Category & Reading time -->
                  <div class="flex items-center justify-between text-xs text-slate-400 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-100">
                      <?= e($post['category_name'] ?: 'Technical SEO') ?>
                    </span>
                    <span class="font-mono text-[11px]"><?= (int)$post['reading_time'] ?> min read</span>
                  </div>

                  <!-- Title -->
                  <h2 class="text-lg font-bold tracking-tight text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2">
                    <a href="/blog/<?= e($post['slug']) ?>/">
                      <?= e($post['title']) ?>
                    </a>
                  </h2>

                  <!-- Excerpt -->
                  <p class="text-xs sm:text-sm text-slate-600 mt-3 line-clamp-3 leading-relaxed">
                    <?= e($post['excerpt']) ?>
                  </p>
                </div>

                <!-- Footer info -->
                <div class="p-6 sm:p-7 pt-0 border-t border-slate-100 mt-4 flex items-center justify-between text-xs">
                  <div class="text-[11px] text-slate-400 font-mono">
                    <?= date('M d, Y', strtotime($post['publish_date'])) ?>
                  </div>
                  <a href="/blog/<?= e($post['slug']) ?>/" class="font-bold text-blue-700 hover:text-blue-900 flex items-center space-x-1">
                    <span>Read Article</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                  </a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Global Conversion Section -->
    <section class="bg-slate-900 text-white py-16 sm:py-20 border-t border-slate-800">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
        <h2 class="text-2xl sm:text-4xl font-extrabold tracking-tight">Need Senior Technical SEO Execution?</h2>
        <p class="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto">
          Skip generic agencies. Work directly with an independent technical search consultant to diagnose indexing bottlenecks, optimize crawl efficiency, and grow high-intent organic traffic.
        </p>
        <div class="pt-2">
          <a href="https://calendly.com/dhanajiprajapati12" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-6 py-3.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-xl shadow-blue-600/20 transition-all">
            Book a 30-Minute Strategy Call &rarr;
          </a>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="bg-slate-950 text-slate-400 py-12 px-4 sm:px-6 lg:px-8 border-t border-slate-900 text-xs">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
      <p>&copy; <?= date('Y') ?> Dhanaji Prajapati. All rights reserved. Independent SEO Consultant.</p>
      <div class="flex items-center space-x-4">
        <a href="/white-label-seo/" class="hover:text-white transition-colors">White Label SEO</a>
        <span>&middot;</span>
        <a href="/pricing/" class="hover:text-white transition-colors">Pricing</a>
        <span>&middot;</span>
        <a href="/contact/" class="hover:text-white transition-colors">Contact</a>
      </div>
    </div>
  </footer>

  <script src="/assets/js/main.js"></script>

  <!-- Sticky Floating Conversion Banner -->
  <aside id="sticky-floating-banner" aria-label="Special Consultation Offer" class="fixed bottom-0 inset-x-0 z-40 bg-slate-950/95 backdrop-blur-md text-slate-200 border-t border-slate-800 shadow-2xl py-2.5 px-4 sm:py-3 sm:px-6 transition-all duration-300">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
      <div class="flex items-center space-x-2.5 min-w-0">
        <span class="relative flex h-2.5 w-2.5 shrink-0">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
        </span>
        <div class="text-xs sm:text-sm truncate">
          <strong class="text-white font-bold tracking-tight">Unlock Hidden Growth</strong>
          <span class="text-slate-500 mx-1.5 hidden sm:inline">&mdash;</span>
          <span class="text-slate-300 hidden sm:inline">Claim Your First Month Free</span>
        </div>
      </div>
      <div class="flex items-center space-x-2 shrink-0">
        <a href="https://calendly.com/dhanajiprajapati12" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-3.5 py-1.5 sm:px-4 sm:py-2 rounded text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition-colors shadow-xs whitespace-nowrap">
          Claim Now &rarr;
        </a>
        <button id="close-sticky-banner" type="button" class="p-1 text-slate-400 hover:text-white rounded transition-colors focus:outline-none" aria-label="Dismiss banner">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </div>
  </aside>

</body>
</html>
