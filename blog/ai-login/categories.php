<?php
$pageTitle = 'Categories';
require_once __DIR__ . '/admin-header.php';

$pdo = get_db();
$message = '';
$messageType = 'success';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $message = 'Security validation failed.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($slug) && !empty($name)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            }

            if (empty($name)) {
                $message = 'Category name is required.';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $slug, $description]);
                    $message = 'Category created successfully!';
                } catch (Exception $e) {
                    $message = 'Error creating category. Slug must be unique.';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'delete') {
            $catId = (int)($_POST['category_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$catId]);
            $message = 'Category removed successfully.';
        }
    }
}

// Fetch all categories with post count
$categories = $pdo->query("
    SELECT c.id, c.name, c.slug, c.description, c.created_at, COUNT(p.id) AS post_count
    FROM categories c
    LEFT JOIN posts p ON c.id = p.category_id
    GROUP BY c.id, c.name, c.slug, c.description, c.created_at
    ORDER BY c.name ASC
")->fetchAll();

$csrf_token = get_csrf_token();
?>

<div class="space-y-6">
  <div class="pb-6 border-b border-slate-800">
    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Categories</h1>
    <p class="text-xs sm:text-sm text-slate-400 mt-1">
      Taxonomic classifications for organizing technical search guides.
    </p>
  </div>

  <?php if (!empty($message)): ?>
    <div class="p-3.5 rounded-xl text-xs flex items-center space-x-2 <?= $messageType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300' : 'bg-rose-500/10 border border-rose-500/30 text-rose-300' ?>">
      <span><?= e($message) ?></span>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Add Category Form (Cols 1 to 4) -->
    <div class="lg:col-span-4">
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4">
        <h2 class="text-xs font-bold text-white uppercase tracking-wider pb-2 border-b border-slate-800">
          Add New Category
        </h2>
        
        <form method="POST" action="/blog/ai-login/categories" class="space-y-4">
          <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
          <input type="hidden" name="action" value="create">

          <div>
            <label for="cat-name" class="block text-xs font-semibold text-slate-300 mb-1">Name <span class="text-rose-400">*</span></label>
            <input type="text" id="cat-name" name="name" required placeholder="e.g. Core Web Vitals"
              class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
          </div>

          <div>
            <label for="cat-slug" class="block text-xs font-semibold text-slate-400 mb-1">Slug</label>
            <input type="text" id="cat-slug" name="slug" placeholder="core-web-vitals"
              class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-mono placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
          </div>

          <div>
            <label for="cat-desc" class="block text-xs font-semibold text-slate-400 mb-1">Description</label>
            <textarea id="cat-desc" name="description" rows="3" placeholder="Category thematic description..."
              class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
          </div>

          <button type="submit" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow-md transition-colors">
            + Create Category
          </button>
        </form>
      </div>
    </div>

    <!-- Category List (Cols 5 to 12) -->
    <div class="lg:col-span-8">
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-800 text-xs font-semibold text-slate-400">
          All Registered Categories (<?= count($categories) ?>)
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
              <tr>
                <th class="py-3 px-4">Name &amp; Slug</th>
                <th class="py-3 px-4">Description</th>
                <th class="py-3 px-4 text-center">Articles</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <?php foreach ($categories as $cat): ?>
                <tr class="hover:bg-slate-900/40 transition-colors">
                  <td class="py-3.5 px-4 font-semibold text-white">
                    <?= e($cat['name']) ?>
                    <span class="block text-[10px] text-slate-500 font-mono mt-0.5">/category/<?= e($cat['slug']) ?></span>
                  </td>
                  <td class="py-3.5 px-4 text-slate-400 max-w-xs text-xs">
                    <?= e($cat['description'] ?? 'No description') ?>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                      <?= (int)$cat['post_count'] ?>
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-right whitespace-nowrap">
                    <form method="POST" action="/blog/ai-login/categories" class="inline" onsubmit="return confirm('Delete this category? Associated posts will become uncategorized.');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
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
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
