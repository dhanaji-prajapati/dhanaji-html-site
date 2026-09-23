<?php
$pageTitle = 'Manage Posts';
require_once __DIR__ . '/admin-header.php';

$pdo = get_db();
$message = '';
$messageType = 'success';

// Handle Post Deletion (POST with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $message = 'Security validation failed. Please try again.';
        $messageType = 'error';
    } else {
        $delStmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $delStmt->execute([$postId]);
        $message = 'Post successfully deleted.';
        $messageType = 'success';
    }
}

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$categoryFilter = (int)($_GET['category'] ?? 0);
$sortBy = trim($_GET['sort'] ?? 'newest');

// Build query
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(p.title LIKE ? OR p.excerpt LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (in_array($statusFilter, ['published', 'draft', 'scheduled'])) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Sort order
$orderClause = 'ORDER BY p.publish_date DESC';
if ($sortBy === 'oldest') $orderClause = 'ORDER BY p.publish_date ASC';
if ($sortBy === 'updated') $orderClause = 'ORDER BY p.updated_at DESC';
if ($sortBy === 'title') $orderClause = 'ORDER BY p.title ASC';

$sql = "
    SELECT p.id, p.title, p.slug, p.status, p.publish_date, p.updated_at, p.is_featured,
           p.reading_time, c.name AS category_name, u.display_name AS author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    {$whereClause}
    {$orderClause}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get categories for filter dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$csrf_token = get_csrf_token();
?>

<div class="space-y-6">
  <!-- Top bar -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
    <div>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Manage Posts</h1>
      <p class="text-xs sm:text-sm text-slate-400 mt-1">
        Browse, search, edit, and organize all published and draft articles.
      </p>
    </div>
    <div>
      <a href="/blog/ai-login/post-editor" class="inline-flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-600/20 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        <span>Create New Post</span>
      </a>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <div class="p-3.5 rounded-xl text-xs flex items-center space-x-2 <?= $messageType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300' : 'bg-rose-500/10 border border-rose-500/30 text-rose-300' ?>">
      <span><?= e($message) ?></span>
    </div>
  <?php endif; ?>

  <!-- Search & Filter Controls -->
  <form method="GET" action="/blog/ai-login/posts" class="bg-slate-950/70 border border-slate-800 rounded-2xl p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 text-xs">
    <!-- Keyword Search -->
    <div class="lg:col-span-4">
      <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Search Keywords</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Title or abstract..."
        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
    </div>

    <!-- Status Filter -->
    <div class="lg:col-span-3">
      <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Status</label>
      <select name="status" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
        <option value="">All Statuses</option>
        <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="scheduled" <?= $statusFilter === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
      </select>
    </div>

    <!-- Category Filter -->
    <div class="lg:col-span-3">
      <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Category</label>
      <select name="category" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Sort Order & Submit -->
    <div class="lg:col-span-2 flex items-end space-x-2">
      <select name="sort" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest Date</option>
        <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest Date</option>
        <option value="updated" <?= $sortBy === 'updated' ? 'selected' : '' ?>>Recently Updated</option>
        <option value="title" <?= $sortBy === 'title' ? 'selected' : '' ?>>Title (A-Z)</option>
      </select>
      <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg font-semibold transition-colors">
        Filter
      </button>
    </div>
  </form>

  <!-- Posts Table -->
  <div class="bg-slate-950/70 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="p-4 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400">
      <span>Found <strong class="text-white font-mono"><?= count($posts) ?></strong> post(s)</span>
      <?php if (!empty($search) || !empty($statusFilter) || $categoryFilter > 0): ?>
        <a href="/blog/ai-login/posts" class="text-blue-400 hover:underline">Reset Filters</a>
      <?php endif; ?>
    </div>

    <?php if (empty($posts)): ?>
      <div class="p-12 text-center text-slate-500 text-xs">
        No posts match your selected criteria.
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
          <thead class="bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
            <tr>
              <th class="py-3 px-4">Title &amp; Slug</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4">Category</th>
              <th class="py-3 px-4">Author</th>
              <th class="py-3 px-4">Published Date</th>
              <th class="py-3 px-4">Updated Date</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60">
            <?php foreach ($posts as $post): ?>
              <tr class="hover:bg-slate-900/40 transition-colors">
                <td class="py-3.5 px-4 font-medium text-white max-w-sm">
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
                <td class="py-3.5 px-4 text-slate-400">
                  <?= e($post['author_name'] ?? 'Dhanaji') ?>
                </td>
                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400">
                  <?= date('M d, Y', strtotime($post['publish_date'])) ?>
                </td>
                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400">
                  <?= date('M d, Y', strtotime($post['updated_at'])) ?>
                </td>
                <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                  <a href="/blog/ai-login/post-editor?id=<?= $post['id'] ?>" class="text-blue-400 hover:text-blue-300 font-semibold">
                    Edit
                  </a>
                  <span class="text-slate-600">&middot;</span>
                  <a href="/blog/<?= e($post['slug']) ?>/" target="_blank" class="text-slate-400 hover:text-white">
                    Preview
                  </a>
                  <span class="text-slate-600">&middot;</span>
                  <form method="POST" action="/blog/ai-login/posts" class="inline" onsubmit="return confirm('Are you sure you want to delete this post?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                    <button type="submit" class="text-rose-400 hover:text-rose-300 font-semibold">
                      Delete
                    </button>
                  </form>
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
