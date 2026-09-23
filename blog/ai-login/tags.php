<?php
$pageTitle = 'Tags';
require_once __DIR__ . '/admin-header.php';

$pdo = get_db();
$message = '';
$messageType = 'success';

// Handle Add/Delete
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

            if (empty($slug) && !empty($name)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            }

            if (empty($name)) {
                $message = 'Tag name is required.';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                    $stmt->execute([$name, $slug]);
                    $message = 'Tag added successfully!';
                } catch (Exception $e) {
                    $message = 'Error adding tag. Slug must be unique.';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'delete') {
            $tagId = (int)($_POST['tag_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
            $stmt->execute([$tagId]);
            $message = 'Tag deleted successfully.';
        }
    }
}

// Fetch all tags with post count
$tags = $pdo->query("
    SELECT t.id, t.name, t.slug, t.created_at, COUNT(pt.post_id) AS post_count
    FROM tags t
    LEFT JOIN post_tags pt ON t.id = pt.tag_id
    GROUP BY t.id, t.name, t.slug, t.created_at
    ORDER BY t.name ASC
")->fetchAll();

$csrf_token = get_csrf_token();
?>

<div class="space-y-6">
  <div class="pb-6 border-b border-slate-800">
    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Tags</h1>
    <p class="text-xs sm:text-sm text-slate-400 mt-1">
      Granular semantic tags for technical indexing topics and cross-linking.
    </p>
  </div>

  <?php if (!empty($message)): ?>
    <div class="p-3.5 rounded-xl text-xs flex items-center space-x-2 <?= $messageType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300' : 'bg-rose-500/10 border border-rose-500/30 text-rose-300' ?>">
      <span><?= e($message) ?></span>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Add Tag Form (Cols 1 to 4) -->
    <div class="lg:col-span-4">
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4">
        <h2 class="text-xs font-bold text-white uppercase tracking-wider pb-2 border-b border-slate-800">
          Add New Tag
        </h2>
        
        <form method="POST" action="/blog/ai-login/tags" class="space-y-4">
          <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
          <input type="hidden" name="action" value="create">

          <div>
            <label for="tag-name" class="block text-xs font-semibold text-slate-300 mb-1">Tag Name <span class="text-rose-400">*</span></label>
            <input type="text" id="tag-name" name="name" required placeholder="e.g. Server Logs"
              class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
          </div>

          <div>
            <label for="tag-slug" class="block text-xs font-semibold text-slate-400 mb-1">Slug</label>
            <input type="text" id="tag-slug" name="slug" placeholder="server-logs"
              class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-mono placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
          </div>

          <button type="submit" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow-md transition-colors">
            + Create Tag
          </button>
        </form>
      </div>
    </div>

    <!-- Tag List (Cols 5 to 12) -->
    <div class="lg:col-span-8">
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-800 text-xs font-semibold text-slate-400">
          All Tags (<?= count($tags) ?>)
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
              <tr>
                <th class="py-3 px-4">Tag Name</th>
                <th class="py-3 px-4">Slug</th>
                <th class="py-3 px-4 text-center">Tagged Articles</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <?php foreach ($tags as $tag): ?>
                <tr class="hover:bg-slate-900/40 transition-colors">
                  <td class="py-3.5 px-4 font-semibold text-white">
                    <?= e($tag['name']) ?>
                  </td>
                  <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400">
                    <?= e($tag['slug']) ?>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                      <?= (int)$tag['post_count'] ?>
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-right whitespace-nowrap">
                    <form method="POST" action="/blog/ai-login/tags" class="inline" onsubmit="return confirm('Delete this tag?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="tag_id" value="<?= $tag['id'] ?>">
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
