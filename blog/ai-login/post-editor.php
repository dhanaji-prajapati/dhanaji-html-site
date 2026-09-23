<?php
$pageTitle = 'Edit Post';
require_once __DIR__ . '/admin-header.php';

$pdo = get_db();
$postId = (int)($_GET['id'] ?? 0);
$isEditing = $postId > 0;
$message = '';
$messageType = 'success';

// Default values
$post = [
    'id' => 0,
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'content' => '',
    'featured_image' => '',
    'featured_image_alt' => '',
    'author_id' => $adminUser['id'],
    'category_id' => null,
    'status' => 'draft',
    'publish_date' => date('Y-m-d\TH:i'),
    'seo_title' => '',
    'meta_description' => '',
    'canonical_url' => '',
    'robots' => 'index, follow',
    'og_title' => '',
    'og_description' => '',
    'og_image' => '',
    'is_featured' => 0,
    'show_in_archive' => 1,
    'allow_comments' => 0,
    'reading_time' => 5
];
$selectedTagIds = [];

// Load existing post if editing
if ($isEditing) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $post = $existing;
        // Format publish date for datetime-local input
        $post['publish_date'] = date('Y-m-d\TH:i', strtotime($post['publish_date']));
        
        // Load tags
        $tagStmt = $pdo->prepare("SELECT tag_id FROM post_tags WHERE post_id = ?");
        $tagStmt->execute([$postId]);
        $selectedTagIds = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $isEditing = false;
        $postId = 0;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $message = 'Security token invalid. Please refresh the page.';
        $messageType = 'error';
    } else {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = $_POST['content'] ?? '';
        $featured_image = trim($_POST['featured_image'] ?? '');
        $featured_image_alt = trim($_POST['featured_image_alt'] ?? '');
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $status = in_array($_POST['status'] ?? '', ['draft', 'published', 'scheduled']) ? $_POST['status'] : 'draft';
        $publish_date = !empty($_POST['publish_date']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_date'])) : date('Y-m-d H:i:s');
        
        $seo_title = trim($_POST['seo_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $canonical_url = trim($_POST['canonical_url'] ?? '');
        $robots = trim($_POST['robots'] ?? 'index, follow');
        $og_title = trim($_POST['og_title'] ?? '');
        $og_description = trim($_POST['og_description'] ?? '');
        $og_image = trim($_POST['og_image'] ?? '');
        
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $show_in_archive = isset($_POST['show_in_archive']) ? 1 : 0;
        $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
        
        // Calculate reading time
        $reading_time = (int)($_POST['reading_time'] ?? 0);
        if ($reading_time <= 0) {
            $words = str_word_count(strip_tags($content));
            $reading_time = max(1, (int)ceil($words / 200));
        }

        // Auto-generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }

        if (empty($title)) {
            $message = 'Please provide an article title.';
            $messageType = 'error';
        } else {
            // Check unique slug
            $checkSql = "SELECT id FROM posts WHERE slug = ? AND id != ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$slug, $postId]);
            if ($checkStmt->fetch()) {
                $message = 'This URL slug is already taken. Please enter a unique slug.';
                $messageType = 'error';
            } else {
                $now = date('Y-m-d H:i:s');
                if ($isEditing) {
                    $updateSql = "
                        UPDATE posts SET
                            title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?,
                            featured_image_alt = ?, category_id = ?, status = ?, publish_date = ?,
                            seo_title = ?, meta_description = ?, canonical_url = ?, robots = ?,
                            og_title = ?, og_description = ?, og_image = ?, is_featured = ?,
                            show_in_archive = ?, allow_comments = ?, reading_time = ?, updated_at = ?
                        WHERE id = ?
                    ";
                    $stmt = $pdo->prepare($updateSql);
                    $stmt->execute([
                        $title, $slug, $excerpt, $content, $featured_image,
                        $featured_image_alt, $category_id, $status, $publish_date,
                        $seo_title, $meta_description, $canonical_url, $robots,
                        $og_title, $og_description, $og_image, $is_featured,
                        $show_in_archive, $allow_comments, $reading_time, $now,
                        $postId
                    ]);
                    $message = 'Post updated successfully in database!';
                } else {
                    $insertSql = "
                        INSERT INTO posts (
                            title, slug, excerpt, content, featured_image, featured_image_alt,
                            author_id, category_id, status, publish_date, seo_title, meta_description,
                            canonical_url, robots, og_title, og_description, og_image, is_featured,
                            show_in_archive, allow_comments, reading_time, created_at, updated_at
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?
                        )
                    ";
                    $stmt = $pdo->prepare($insertSql);
                    $stmt->execute([
                        $title, $slug, $excerpt, $content, $featured_image, $featured_image_alt,
                        $adminUser['id'], $category_id, $status, $publish_date, $seo_title, $meta_description,
                        $canonical_url, $robots, $og_title, $og_description, $og_image, $is_featured,
                        $show_in_archive, $allow_comments, $reading_time, $now, $now
                    ]);
                    $postId = (int)$pdo->lastInsertId();
                    $isEditing = true;
                    $message = 'New post created successfully!';
                }

                // Sync tags
                $postTags = isset($_POST['tags']) && is_array($_POST['tags']) ? array_map('intval', $_POST['tags']) : [];
                $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$postId]);
                if (!empty($postTags)) {
                    $tagInsert = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                    foreach ($postTags as $tId) {
                        $tagInsert->execute([$postId, $tId]);
                    }
                }
                $selectedTagIds = $postTags;

                // Reload post
                $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
                $stmt->execute([$postId]);
                $post = $stmt->fetch();
                $post['publish_date'] = date('Y-m-d\TH:i', strtotime($post['publish_date']));
            }
        }
    }
}

// Load categories & tags
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$allTags = $pdo->query("SELECT id, name FROM tags ORDER BY name ASC")->fetchAll();
$mediaItems = $pdo->query("SELECT id, filename, filepath FROM media ORDER BY id DESC LIMIT 12")->fetchAll();
$csrf_token = get_csrf_token();
?>

<div class="space-y-6">
  <!-- Top bar -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
    <div class="flex items-center space-x-3">
      <a href="/blog/ai-login/posts" class="p-2 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors" title="Back to posts">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
          <?= $isEditing ? 'Edit Post' : 'Create New Post' ?>
        </h1>
        <p class="text-xs text-slate-400 font-mono mt-0.5">
          <?= $isEditing ? '/blog/' . e($post['slug']) . '/' : 'Drafting new technical article' ?>
        </p>
      </div>
    </div>
    
    <div class="flex items-center space-x-3">
      <?php if ($isEditing): ?>
        <a href="/blog/<?= e($post['slug']) ?>/" target="_blank" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition-colors flex items-center space-x-1.5">
          <span>Preview Public URL</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
      <?php endif; ?>
      <button type="submit" form="post-form" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-600/20 transition-all flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <span><?= $isEditing ? 'Save Changes' : 'Publish Article' ?></span>
      </button>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <div class="p-3.5 rounded-xl text-xs flex items-center space-x-2 <?= $messageType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300' : 'bg-rose-500/10 border border-rose-500/30 text-rose-300' ?>">
      <span><?= e($message) ?></span>
    </div>
  <?php endif; ?>

  <form id="post-form" method="POST" action="/blog/ai-login/post-editor<?= $isEditing ? '?id=' . $postId : '' ?>" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

    <!-- Main Content Left Column (Cols 1 to 8) -->
    <div class="lg:col-span-8 space-y-6">
      
      <!-- Basic: Title & Slug -->
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 space-y-4">
        <div>
          <label for="post-title" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
            Article Title <span class="text-rose-400">*</span>
          </label>
          <input type="text" id="post-title" name="title" required value="<?= e($post['title']) ?>"
            placeholder="e.g. How to Fix Crawlability and Indexing Issues"
            class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-base font-semibold placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
          <label for="post-slug" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
            URL Slug <span class="text-[11px] font-normal lowercase text-slate-500">(Auto-generated or custom)</span>
          </label>
          <div class="flex items-center">
            <span class="px-3 py-2 bg-slate-900 border border-r-0 border-slate-700 text-slate-500 text-xs font-mono rounded-l-xl">/blog/</span>
            <input type="text" id="post-slug" name="slug" value="<?= e($post['slug']) ?>"
              placeholder="how-to-fix-crawlability-and-indexing-issues"
              class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-r-xl text-white text-xs font-mono focus:outline-none focus:ring-1 focus:ring-blue-500">
          </div>
        </div>

        <div>
          <label for="post-excerpt" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
            Excerpt / Technical Abstract
          </label>
          <textarea id="post-excerpt" name="excerpt" rows="3"
            placeholder="Brief technical summary appearing in search previews, terminal cards, and social snippets..."
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500"><?= e($post['excerpt']) ?></textarea>
        </div>
      </div>

      <!-- Rich Content Editor -->
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
          <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center space-x-2">
            <span>Article Body (Rich Editor)</span>
          </label>
          <div class="flex items-center space-x-2 text-xs">
            <button type="button" id="toggle-html-btn" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded font-mono text-[11px] transition-colors">
              &lt;&gt; HTML Source
            </button>
          </div>
        </div>

        <!-- Toolbar (H2, H3, H4, Paragraph, Bold, Italic, Link, OL, UL, Blockquote, Image, Table, Code, HR) -->
        <div id="editor-toolbar" class="bg-slate-900/90 border-b border-slate-800 p-2 flex flex-wrap items-center gap-1.5 text-xs text-slate-300">
          <select id="format-select" class="px-2 py-1 bg-slate-800 border border-slate-700 rounded text-xs text-white focus:outline-none">
            <option value="p">Paragraph</option>
            <option value="h2">Heading 2 (H2)</option>
            <option value="h3">Heading 3 (H3)</option>
            <option value="h4">Heading 4 (H4)</option>
            <option value="blockquote">Quote</option>
            <option value="pre">Code Block</option>
          </select>

          <div class="h-4 w-px bg-slate-700 mx-1"></div>

          <button type="button" data-cmd="bold" class="toolbar-btn px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded font-bold" title="Bold">B</button>
          <button type="button" data-cmd="italic" class="toolbar-btn px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded italic" title="Italic">I</button>
          <button type="button" data-cmd="createLink" class="toolbar-btn px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded" title="Insert Link">Link</button>
          <button type="button" data-cmd="unlink" class="toolbar-btn px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded" title="Remove Link">&times;Link</button>

          <div class="h-4 w-px bg-slate-700 mx-1"></div>

          <button type="button" data-cmd="insertUnorderedList" class="toolbar-btn px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded" title="Bullet List">&bull; List</button>
          <button type="button" data-cmd="insertOrderedList" class="toolbar-btn px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded" title="Numbered List">1. List</button>
          <button type="button" data-cmd="insertHorizontalRule" class="toolbar-btn px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded" title="Horizontal Divider">&mdash; HR</button>

          <div class="h-4 w-px bg-slate-700 mx-1"></div>

          <button type="button" id="insert-code-inline-btn" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded font-mono text-[11px]" title="Inline Code">&lt;code&gt;</button>
          <button type="button" id="insert-table-btn" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded" title="Insert Table">Table</button>
          <button type="button" id="insert-image-prompt-btn" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded" title="Insert Image">Image</button>
        </div>

        <!-- Editable Area -->
        <div id="visual-editor" contenteditable="true" data-placeholder="Start typing your comprehensive technical article here..."
          class="min-h-[420px] p-6 text-slate-200 text-sm leading-relaxed focus:outline-none overflow-y-auto max-h-[700px] prose prose-invert max-w-none">
          <?= $post['content'] ?>
        </div>

        <!-- Raw HTML Source Textarea (Hidden by default) -->
        <textarea id="raw-content-textarea" name="content" class="hidden w-full min-h-[420px] p-6 bg-slate-950 font-mono text-xs text-slate-300 border-0 focus:outline-none"><?= e($post['content']) ?></textarea>
      </div>

      <!-- SEO Optimization Box (SEO Title, Meta Description, Canonical, Robots) -->
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center space-x-2">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span>Search Engine Optimization (SEO Metadata)</span>
          </h2>
          <span class="text-[11px] text-slate-400 font-mono">Dynamic Head Tag Injection</span>
        </div>

        <div>
          <label for="seo_title" class="block text-xs font-semibold text-slate-400 mb-1">SEO Title (Title Tag)</label>
          <input type="text" id="seo_title" name="seo_title" value="<?= e($post['seo_title']) ?>"
            placeholder="Custom title tag (defaults to post title if blank) | Dhanaji Prajapati"
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <div>
          <label for="meta_description" class="block text-xs font-semibold text-slate-400 mb-1">Meta Description</label>
          <textarea id="meta_description" name="meta_description" rows="2"
            placeholder="Recommended 140-160 characters describing technical value..."
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500"><?= e($post['meta_description']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="canonical_url" class="block text-xs font-semibold text-slate-400 mb-1">Canonical URL</label>
            <input type="url" id="canonical_url" name="canonical_url" value="<?= e($post['canonical_url']) ?>"
              placeholder="https://dhanajiprajapati.com/blog/..."
              class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
          </div>

          <div>
            <label for="robots" class="block text-xs font-semibold text-slate-400 mb-1">Robots Directives</label>
            <input type="text" id="robots" name="robots" value="<?= e($post['robots']) ?>"
              placeholder="index, follow"
              class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
          </div>
        </div>
      </div>

      <!-- Social & Open Graph Metadata (OG Title, OG Description, OG Image) -->
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 space-y-4">
        <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider pb-3 border-b border-slate-800 flex items-center space-x-2">
          <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
          <span>Social Media &amp; Open Graph (OG)</span>
        </h2>

        <div>
          <label for="og_title" class="block text-xs font-semibold text-slate-400 mb-1">OG Title</label>
          <input type="text" id="og_title" name="og_title" value="<?= e($post['og_title']) ?>"
            placeholder="Title displayed when shared on LinkedIn, X, Slack..."
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <div>
          <label for="og_description" class="block text-xs font-semibold text-slate-400 mb-1">OG Description</label>
          <textarea id="og_description" name="og_description" rows="2"
            placeholder="Description displayed on social card previews..."
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500"><?= e($post['og_description']) ?></textarea>
        </div>

        <div>
          <label for="og_image" class="block text-xs font-semibold text-slate-400 mb-1">OG Share Image URL</label>
          <input type="text" id="og_image" name="og_image" value="<?= e($post['og_image']) ?>"
            placeholder="/uploads/blog/... or full image URL"
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
      </div>

    </div>

    <!-- Publishing & Taxonomies Right Column (Cols 9 to 12) -->
    <div class="lg:col-span-4 space-y-6">
      
      <!-- Publishing Status & Date -->
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4">
        <h3 class="text-xs font-bold text-white uppercase tracking-wider pb-3 border-b border-slate-800">
          Publishing Controls
        </h3>

        <div>
          <label for="post-status" class="block text-xs font-semibold text-slate-400 mb-1">Status</label>
          <select id="post-status" name="status" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft (Private)</option>
            <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published (Public)</option>
            <option value="scheduled" <?= $post['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled (Future Release)</option>
          </select>
        </div>

        <div>
          <label for="publish_date" class="block text-xs font-semibold text-slate-400 mb-1">Publish Date &amp; Time</label>
          <input type="datetime-local" id="publish_date" name="publish_date" value="<?= e($post['publish_date']) ?>"
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs font-mono focus:outline-none focus:ring-1 focus:ring-blue-500">
          <p class="text-[10px] text-slate-500 mt-1">If scheduled, the article auto-publishes when this time is reached.</p>
        </div>

        <div class="pt-2 border-t border-slate-800/80">
          <button type="submit" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow-md transition-colors text-center">
            <?= $isEditing ? 'Save Article' : 'Publish / Schedule' ?>
          </button>
        </div>
      </div>

      <!-- Category & Tags -->
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4">
        <h3 class="text-xs font-bold text-white uppercase tracking-wider pb-3 border-b border-slate-800">
          Taxonomies
        </h3>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label for="category_id" class="text-xs font-semibold text-slate-400">Primary Category</label>
            <a href="/blog/ai-login/categories" target="_blank" class="text-[10px] text-blue-400 hover:underline">+ Manage</a>
          </div>
          <select id="category_id" name="category_id" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $post['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-semibold text-slate-400">Article Tags</label>
            <a href="/blog/ai-login/tags" target="_blank" class="text-[10px] text-blue-400 hover:underline">+ Manage</a>
          </div>
          <div class="max-h-36 overflow-y-auto p-2 bg-slate-900 border border-slate-700 rounded-xl space-y-1.5 text-xs">
            <?php if (empty($allTags)): ?>
              <span class="text-slate-500 text-[11px]">No tags created yet.</span>
            <?php else: ?>
              <?php foreach ($allTags as $tag): ?>
                <label class="flex items-center space-x-2 text-slate-300 hover:text-white cursor-pointer">
                  <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>"
                    <?= in_array((int)$tag['id'], $selectedTagIds) ? 'checked' : '' ?>
                    class="rounded border-slate-700 text-blue-600 focus:ring-blue-500 bg-slate-800">
                  <span class="text-[11px]"><?= e($tag['name']) ?></span>
                </label>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Featured Image -->
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4">
        <h3 class="text-xs font-bold text-white uppercase tracking-wider pb-3 border-b border-slate-800">
          Featured Image
        </h3>

        <div>
          <label for="featured_image" class="block text-xs font-semibold text-slate-400 mb-1">Image URL / Filepath</label>
          <div class="flex items-center space-x-2">
            <input type="text" id="featured_image" name="featured_image" value="<?= e($post['featured_image']) ?>"
              placeholder="/uploads/blog/... or image URL"
              class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            <a href="/blog/ai-login/media" target="_blank" class="px-2.5 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-300 text-xs shrink-0" title="Open Media Library">
              Library
            </a>
          </div>
        </div>

        <div>
          <label for="featured_image_alt" class="block text-xs font-semibold text-slate-400 mb-1">Image Alt Text (SEO)</label>
          <input type="text" id="featured_image_alt" name="featured_image_alt" value="<?= e($post['featured_image_alt']) ?>"
            placeholder="Descriptive alt text for search crawlers"
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <div id="image-preview-container" class="<?= empty($post['featured_image']) ? 'hidden' : '' ?> pt-2">
          <img id="image-preview" src="<?= e($post['featured_image']) ?>" alt="Preview" class="w-full h-32 object-cover rounded-xl border border-slate-800">
        </div>
      </div>

      <!-- Additional Settings (Featured, Archive, Comments, Reading Time) -->
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-3">
        <h3 class="text-xs font-bold text-white uppercase tracking-wider pb-2 border-b border-slate-800">
          Display &amp; Architecture
        </h3>

        <label class="flex items-center space-x-2.5 cursor-pointer text-xs text-slate-300 hover:text-white">
          <input type="checkbox" name="is_featured" value="1" <?= $post['is_featured'] ? 'checked' : '' ?>
            class="rounded border-slate-700 text-blue-600 focus:ring-blue-500 bg-slate-900">
          <span>Mark as Featured Post</span>
        </label>

        <label class="flex items-center space-x-2.5 cursor-pointer text-xs text-slate-300 hover:text-white">
          <input type="checkbox" name="show_in_archive" value="1" <?= $post['show_in_archive'] ? 'checked' : '' ?>
            class="rounded border-slate-700 text-blue-600 focus:ring-blue-500 bg-slate-900">
          <span>Show in Blog Archive Listing</span>
        </label>

        <label class="flex items-center space-x-2.5 cursor-pointer text-xs text-slate-300 hover:text-white">
          <input type="checkbox" name="allow_comments" value="1" <?= $post['allow_comments'] ? 'checked' : '' ?>
            class="rounded border-slate-700 text-blue-600 focus:ring-blue-500 bg-slate-900">
          <span>Allow Comments / Inquiries</span>
        </label>

        <div class="pt-2">
          <label for="reading_time" class="block text-xs font-semibold text-slate-400 mb-1">
            Reading Time (Minutes)
          </label>
          <input type="number" id="reading_time" name="reading_time" min="1" max="120" value="<?= (int)$post['reading_time'] ?>"
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
      </div>

    </div>
  </form>
</div>

<!-- Rich Content Editor Script (Pure Vanilla JS) -->
<script>
(function() {
  const visualEditor = document.getElementById('visual-editor');
  const rawTextarea = document.getElementById('raw-content-textarea');
  const toggleHtmlBtn = document.getElementById('toggle-html-btn');
  const formatSelect = document.getElementById('format-select');
  const postForm = document.getElementById('post-form');
  const postTitle = document.getElementById('post-title');
  const postSlug = document.getElementById('post-slug');
  const featuredImageInput = document.getElementById('featured_image');
  const imagePreview = document.getElementById('image-preview');
  const imagePreviewContainer = document.getElementById('image-preview-container');

  let isHtmlMode = false;

  // Auto-slug generator on title blur if slug is empty
  postTitle.addEventListener('blur', () => {
    if (!postSlug.value.trim() && postTitle.value.trim()) {
      postSlug.value = postTitle.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    }
  });

  // Featured image preview
  featuredImageInput.addEventListener('input', () => {
    const url = featuredImageInput.value.trim();
    if (url) {
      imagePreview.src = url;
      imagePreviewContainer.classList.remove('hidden');
    } else {
      imagePreviewContainer.classList.add('hidden');
    }
  });

  // Sync visual editor -> textarea on any change
  function syncToTextarea() {
    if (!isHtmlMode) {
      rawTextarea.value = visualEditor.innerHTML;
    }
  }

  visualEditor.addEventListener('input', syncToTextarea);

  // Sync before form submit
  postForm.addEventListener('submit', () => {
    if (isHtmlMode) {
      visualEditor.innerHTML = rawTextarea.value;
    } else {
      rawTextarea.value = visualEditor.innerHTML;
    }
  });

  // Toggle HTML mode
  toggleHtmlBtn.addEventListener('click', () => {
    isHtmlMode = !isHtmlMode;
    if (isHtmlMode) {
      rawTextarea.value = visualEditor.innerHTML;
      visualEditor.classList.add('hidden');
      rawTextarea.classList.remove('hidden');
      toggleHtmlBtn.textContent = '👁 Visual Editor';
    } else {
      visualEditor.innerHTML = rawTextarea.value;
      rawTextarea.classList.add('hidden');
      visualEditor.classList.remove('hidden');
      toggleHtmlBtn.textContent = '<> HTML Source';
    }
  });

  // Toolbar commands
  document.querySelectorAll('#editor-toolbar .toolbar-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const cmd = btn.getAttribute('data-cmd');
      if (cmd === 'createLink') {
        const url = prompt('Enter destination URL:');
        if (url) document.execCommand('createLink', false, url);
      } else {
        document.execCommand(cmd, false, null);
      }
      syncToTextarea();
    });
  });

  // Block format select (H2, H3, H4, Paragraph, Quote, Pre)
  formatSelect.addEventListener('change', () => {
    const val = formatSelect.value;
    if (val) {
      document.execCommand('formatBlock', false, `<${val}>`);
      syncToTextarea();
    }
  });

  // Inline code helper
  document.getElementById('insert-code-inline-btn').addEventListener('click', () => {
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
      const range = selection.getRangeAt(0);
      const text = range.toString() || 'code';
      const codeNode = document.createElement('code');
      codeNode.textContent = text;
      range.deleteContents();
      range.insertNode(codeNode);
      syncToTextarea();
    }
  });

  // Table insert helper
  document.getElementById('insert-table-btn').addEventListener('click', () => {
    const tableHtml = `
      <table class="w-full my-4 border-collapse border border-slate-700 text-xs">
        <thead>
          <tr class="bg-slate-800 text-slate-200">
            <th class="border border-slate-700 p-2 font-bold text-left">Header 1</th>
            <th class="border border-slate-700 p-2 font-bold text-left">Header 2</th>
            <th class="border border-slate-700 p-2 font-bold text-left">Header 3</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="border border-slate-700 p-2">Data Cell 1</td>
            <td class="border border-slate-700 p-2">Data Cell 2</td>
            <td class="border border-slate-700 p-2">Data Cell 3</td>
          </tr>
          <tr>
            <td class="border border-slate-700 p-2">Data Cell 4</td>
            <td class="border border-slate-700 p-2">Data Cell 5</td>
            <td class="border border-slate-700 p-2">Data Cell 6</td>
          </tr>
        </tbody>
      </table>
    `;
    document.execCommand('insertHTML', false, tableHtml);
    syncToTextarea();
  });

  // Image insert helper
  document.getElementById('insert-image-prompt-btn').addEventListener('click', () => {
    const url = prompt('Enter image URL or select from /uploads/blog/:');
    if (url) {
      const alt = prompt('Enter image alt text for SEO:', '') || '';
      const imgHtml = `<img src="${url}" alt="${alt}" class="rounded-xl my-4 max-w-full h-auto border border-slate-700" />`;
      document.execCommand('insertHTML', false, imgHtml);
      syncToTextarea();
    }
  });

})();
</script>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
