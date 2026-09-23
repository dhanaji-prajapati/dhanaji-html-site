<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/admin-header.php';

$pdo = get_db();

// Query real statistics from MySQL
$totalPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$publishedPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
$draftPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn();
$scheduledPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'scheduled'")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalTags = (int)$pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn();
$totalMedia = (int)$pdo->query("SELECT COUNT(*) FROM media")->fetchColumn();

// Query Recent Posts
$recentStmt = $pdo->query("
    SELECT p.id, p.title, p.slug, p.status, p.publish_date, p.updated_at, p.is_featured, c.name AS category_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.updated_at DESC
    LIMIT 6
");
$recentPosts = $recentStmt->fetchAll();
?>

<div class="space-y-8">
  <!-- Welcome and Action Row -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
    <div>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Editorial Dashboard</h1>
      <p class="text-xs sm:text-sm text-slate-400 mt-1">
        Real-time metrics, publishing pipeline, and organic search content repository.
      </p>
    </div>
    
    <!-- Action buttons -->
    <div class="flex items-center flex-wrap gap-2.5">
      <a href="/blog/ai-login/post-editor" class="inline-flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-600/20 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        <span>New Post</span>
      </a>
      <a href="/blog/ai-login/posts" class="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-all">
        <span>Manage Posts</span>
      </a>
      <a href="/blog/ai-login/media" class="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-all">
        <span>Media</span>
      </a>
    </div>
  </div>

  <!-- Metric Cards Grid (Retrieved Live from MySQL) -->
  <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
    <!-- Total Posts -->
    <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
      <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Posts</span>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-3xl font-extrabold text-white font-mono"><?= $totalPosts ?></span>
        <span class="text-[10px] text-blue-400 font-mono">Articles</span>
      </div>
    </div>

    <!-- Published -->
    <div class="bg-slate-950/70 border border-emerald-900/40 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
      <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider flex items-center space-x-1.5">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span>Published</span>
      </span>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-3xl font-extrabold text-emerald-300 font-mono"><?= $publishedPosts ?></span>
        <span class="text-[10px] text-emerald-500 font-mono">Live</span>
      </div>
    </div>

    <!-- Drafts -->
    <div class="bg-slate-950/70 border border-amber-900/40 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
      <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Drafts</span>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-3xl font-extrabold text-amber-300 font-mono"><?= $draftPosts ?></span>
        <span class="text-[10px] text-amber-500 font-mono">Unpublished</span>
      </div>
    </div>

    <!-- Scheduled -->
    <div class="bg-slate-950/70 border border-purple-900/40 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
      <span class="text-xs font-semibold text-purple-400 uppercase tracking-wider">Scheduled</span>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-3xl font-extrabold text-purple-300 font-mono"><?= $scheduledPosts ?></span>
        <span class="text-[10px] text-purple-500 font-mono">Queue</span>
      </div>
    </div>

    <!-- Categories -->
    <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
      <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Categories</span>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-3xl font-extrabold text-slate-200 font-mono"><?= $totalCategories ?></span>
        <a href="/blog/ai-login/categories" class="text-[10px] text-blue-400 hover:underline">Manage</a>
      </div>
    </div>

    <!-- Tags -->
    <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
      <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tags</span>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-3xl font-extrabold text-slate-200 font-mono"><?= $totalTags ?></span>
        <a href="/blog/ai-login/tags" class="text-[10px] text-blue-400 hover:underline">Manage</a>
      </div>
    </div>
  </div>

  <!-- Quick Action Hub (Buttons requested: New Post, Manage Posts, Categories, Tags, Media, Settings, Logout) -->
  <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-5">
    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Admin Command Center</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 text-xs">
      <a href="/blog/ai-login/post-editor" class="p-3 bg-blue-600/10 border border-blue-500/30 rounded-xl text-blue-300 hover:bg-blue-600/20 font-semibold flex flex-col items-center justify-center text-center transition-all">
        <svg class="w-5 h-5 mb-1.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>New Post</span>
      </a>
      <a href="/blog/ai-login/posts" class="p-3 bg-slate-900 border border-slate-800 rounded-xl text-slate-300 hover:bg-slate-800 font-semibold flex flex-col items-center justify-center text-center transition-all">
        <svg class="w-5 h-5 mb-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <span>Manage Posts</span>
      </a>
      <a href="/blog/ai-login/categories" class="p-3 bg-slate-900 border border-slate-800 rounded-xl text-slate-300 hover:bg-slate-800 font-semibold flex flex-col items-center justify-center text-center transition-all">
        <svg class="w-5 h-5 mb-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        <span>Categories</span>
      </a>
      <a href="/blog/ai-login/tags" class="p-3 bg-slate-900 border border-slate-800 rounded-xl text-slate-300 hover:bg-slate-800 font-semibold flex flex-col items-center justify-center text-center transition-all">
        <svg class="w-5 h-5 mb-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
        <span>Tags</span>
      </a>
      <a href="/blog/ai-login/media" class="p-3 bg-slate-900 border border-slate-800 rounded-xl text-slate-300 hover:bg-slate-800 font-semibold flex flex-col items-center justify-center text-center transition-all">
        <svg class="w-5 h-5 mb-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Media</span>
      </a>
      <a href="/blog/ai-login/settings" class="p-3 bg-slate-900 border border-slate-800 rounded-xl text-slate-300 hover:bg-slate-800 font-semibold flex flex-col items-center justify-center text-center transition-all">
        <svg class="w-5 h-5 mb-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Settings</span>
      </a>
      <a href="/blog/ai-login/logout" class="p-3 bg-rose-950/20 border border-rose-900/30 rounded-xl text-rose-300 hover:bg-rose-900/30 font-semibold flex flex-col items-center justify-center text-center transition-all">
        <svg class="w-5 h-5 mb-1.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        <span>Logout</span>
      </a>
    </div>
  </div>

  <!-- Recent Posts Table (Retrieved directly from MySQL) -->
  <div class="bg-slate-950/70 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="p-5 border-b border-slate-800 flex items-center justify-between">
      <div>
        <h2 class="text-base font-bold text-white">Recent Posts</h2>
        <p class="text-xs text-slate-400 mt-0.5">Latest blog publications and drafts in your database.</p>
      </div>
      <a href="/blog/ai-login/posts" class="text-xs font-semibold text-blue-400 hover:underline">
        View All Posts &rarr;
      </a>
    </div>

    <?php if (empty($recentPosts)): ?>
      <div class="p-12 text-center text-slate-500 text-xs">
        No blog posts found in database. Create your first post!
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
          <thead class="bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
            <tr>
              <th class="py-3 px-4">Title</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4">Category</th>
              <th class="py-3 px-4">Publish Date</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60">
            <?php foreach ($recentPosts as $post): ?>
              <tr class="hover:bg-slate-900/40 transition-colors">
                <td class="py-3.5 px-4 font-medium text-white max-w-md">
                  <div class="flex items-center space-x-2">
                    <?php if ($post['is_featured']): ?>
                      <span class="px-1.5 py-0.5 text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded">Featured</span>
                    <?php endif; ?>
                    <a href="/blog/ai-login/post-editor?id=<?= $post['id'] ?>" class="hover:text-blue-400 line-clamp-1">
                      <?= e($post['title']) ?>
                    </a>
                  </div>
                  <span class="text-[10px] text-slate-500 font-mono block mt-0.5">/blog/<?= e($post['slug']) ?>/</span>
                </td>
                <td class="py-3.5 px-4">
                  <?php if ($post['status'] === 'published'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                      Published
                    </span>
                  <?php elseif ($post['status'] === 'draft'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                      Draft
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20">
                      Scheduled
                    </span>
                  <?php endif; ?>
                </td>
                <td class="py-3.5 px-4 text-slate-400">
                  <?= e($post['category_name'] ?? 'Uncategorized') ?>
                </td>
                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400">
                  <?= date('M d, Y', strtotime($post['publish_date'])) ?>
                </td>
                <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                  <a href="/blog/ai-login/post-editor?id=<?= $post['id'] ?>" class="text-blue-400 hover:text-blue-300 font-semibold">
                    Edit
                  </a>
                  <span class="text-slate-600">&middot;</span>
                  <a href="/blog/<?= e($post['slug']) ?>/" target="_blank" class="text-slate-400 hover:text-white">
                    Preview
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
