<?php
$pageTitle = 'Media Library';
require_once __DIR__ . '/admin-header.php';

$pdo = get_db();
$message = '';
$messageType = 'success';

// Ensure upload directory exists
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}

// Handle Upload / Update Alt / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $message = 'Security validation failed.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'upload') {
            if (!empty($_FILES['media_file']['name'])) {
                $file = $_FILES['media_file'];
                $altText = trim($_POST['alt_text'] ?? '');

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $message = 'Upload failed with error code: ' . $file['error'];
                    $messageType = 'error';
                } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
                    $message = 'File size exceeds maximum permitted limit (8MB).';
                    $messageType = 'error';
                } else {
                    // Validate MIME Type strictly
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    // Verify against allowed whitelist
                    $isAllowed = false;
                    foreach (ALLOWED_MEDIA_TYPES as $allowedMime => $exts) {
                        if ($mime === $allowedMime && in_array($extension, $exts, true)) {
                            $isAllowed = true;
                            break;
                        }
                    }

                    // Strict check against any executable or dangerous extensions
                    $blockedExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phar', 'sh', 'py', 'pl', 'cgi', 'exe', 'js', 'html', 'htm'];
                    if (in_array($extension, $blockedExtensions, true)) {
                        $isAllowed = false;
                    }

                    if (!$isAllowed) {
                        $message = 'Invalid file format. Allowed types: JPG, JPEG, PNG, WebP, AVIF, GIF, SVG.';
                        $messageType = 'error';
                    } else {
                        // Generate safe unique filename
                        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
                        $safeFileName = time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '_' . ($baseName ?: 'image') . '.' . $extension;
                        $destination = UPLOAD_DIR . '/' . $safeFileName;
                        $webPath = UPLOAD_URL . '/' . $safeFileName;

                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            // Insert into MySQL media table
                            $stmt = $pdo->prepare("INSERT INTO media (filename, filepath, filetype, filesize, alt_text) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$safeFileName, $webPath, $mime, $file['size'], $altText]);
                            $message = 'Media file uploaded successfully!';
                        } else {
                            $message = 'Failed to move uploaded file to destination.';
                            $messageType = 'error';
                        }
                    }
                }
            } else {
                $message = 'Please select a file to upload.';
                $messageType = 'error';
            }
        } elseif ($action === 'update_alt') {
            $mediaId = (int)($_POST['media_id'] ?? 0);
            $newAlt = trim($_POST['alt_text'] ?? '');
            $stmt = $pdo->prepare("UPDATE media SET alt_text = ? WHERE id = ?");
            $stmt->execute([$newAlt, $mediaId]);
            $message = 'Alt text updated successfully!';
        } elseif ($action === 'delete') {
            $mediaId = (int)($_POST['media_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT filepath FROM media WHERE id = ?");
            $stmt->execute([$mediaId]);
            $item = $stmt->fetch();
            if ($item) {
                $filePath = dirname(__DIR__, 2) . $item['filepath'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                $pdo->prepare("DELETE FROM media WHERE id = ?")->execute([$mediaId]);
                $message = 'Media item deleted.';
            }
        }
    }
}

// Fetch all media items
$mediaItems = $pdo->query("SELECT * FROM media ORDER BY id DESC")->fetchAll();
$csrf_token = get_csrf_token();
?>

<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
    <div>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Media Library</h1>
      <p class="text-xs sm:text-sm text-slate-400 mt-1">
        Upload, manage, and inspect image assets stored in <code class="text-blue-400">/uploads/blog/</code>.
      </p>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <div class="p-3.5 rounded-xl text-xs flex items-center space-x-2 <?= $messageType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300' : 'bg-rose-500/10 border border-rose-500/30 text-rose-300' ?>">
      <span><?= e($message) ?></span>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Upload Form (Cols 1 to 4) -->
    <div class="lg:col-span-4">
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4">
        <h2 class="text-xs font-bold text-white uppercase tracking-wider pb-2 border-b border-slate-800">
          Upload New Image
        </h2>

        <form method="POST" action="/blog/ai-login/media" enctype="multipart/form-data" class="space-y-4">
          <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
          <input type="hidden" name="action" value="upload">

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Select File <span class="text-rose-400">*</span></label>
            <input type="file" name="media_file" required accept=".jpg,.jpeg,.png,.webp,.avif,.gif,.svg"
              class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white hover:file:bg-slate-700">
            <p class="text-[10px] text-slate-500 mt-1">Allowed: JPG, PNG, WebP, AVIF, SVG (Max 8MB)</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Image Alt Text (SEO)</label>
            <input type="text" name="alt_text" placeholder="Descriptive image summary for accessibility & SEO"
              class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
          </div>

          <button type="submit" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow-md transition-colors flex items-center justify-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Upload Image</span>
          </button>
        </form>
      </div>
    </div>

    <!-- Media Gallery Grid (Cols 5 to 12) -->
    <div class="lg:col-span-8">
      <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between text-xs text-slate-400 pb-3 border-b border-slate-800">
          <span>Total Uploaded Assets: <strong class="text-white font-mono"><?= count($mediaItems) ?></strong></span>
        </div>

        <?php if (empty($mediaItems)): ?>
          <div class="p-12 text-center text-slate-500 text-xs">
            No media uploaded yet. Use the upload box on the left to add your first featured image or diagram.
          </div>
        <?php else: ?>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <?php foreach ($mediaItems as $item): ?>
              <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden flex flex-col justify-between group">
                <div class="relative bg-slate-950 h-32 flex items-center justify-center overflow-hidden">
                  <img src="<?= e($item['filepath']) ?>" alt="<?= e($item['alt_text']) ?>" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-200">
                </div>
                
                <div class="p-3 text-xs space-y-2">
                  <div class="font-mono text-[10px] text-slate-400 truncate" title="<?= e($item['filename']) ?>">
                    <?= e($item['filename']) ?>
                  </div>
                  <div class="text-[10px] text-slate-500">
                    <?= number_format($item['filesize'] / 1024, 1) ?> KB &middot; <?= date('M d, Y', strtotime($item['created_at'])) ?>
                  </div>
                  
                  <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                    <button type="button" onclick="navigator.clipboard.writeText('<?= e($item['filepath']) ?>'); alert('Image URL copied to clipboard: <?= e($item['filepath']) ?>');"
                      class="text-blue-400 hover:text-blue-300 font-semibold text-[10px]">
                      Copy URL
                    </button>
                    
                    <form method="POST" action="/blog/ai-login/media" class="inline" onsubmit="return confirm('Delete this image permanently?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="media_id" value="<?= $item['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                      <button type="submit" class="text-rose-400 hover:text-rose-300 text-[10px]">
                        Delete
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
