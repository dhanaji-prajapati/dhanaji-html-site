<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/admin-header.php';

$pdo = get_db();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $message = 'Security validation failed.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $displayName = trim($_POST['display_name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($displayName) || empty($email)) {
                $message = 'Display name and email are required.';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET display_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$displayName, $email, $adminUser['id']]);
                $_SESSION['admin_display_name'] = $displayName;
                $_SESSION['admin_email'] = $email;
                $adminUser['display_name'] = $displayName;
                $adminUser['email'] = $email;
                $message = 'Profile details updated successfully!';
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword)) {
                $message = 'Please provide both current and new password.';
                $messageType = 'error';
            } elseif ($newPassword !== $confirmPassword) {
                $message = 'New passwords do not match.';
                $messageType = 'error';
            } elseif (strlen($newPassword) < 8) {
                $message = 'New password must be at least 8 characters in length.';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$adminUser['id']]);
                $hash = $stmt->fetchColumn();

                if ($hash && password_verify($currentPassword, $hash)) {
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $updateStmt->execute([$newHash, $adminUser['id']]);
                    $message = 'Password changed successfully!';
                } else {
                    $message = 'Current password is incorrect.';
                    $messageType = 'error';
                }
            }
        }
    }
}

$csrf_token = get_csrf_token();
?>

<div class="space-y-6">
  <div class="pb-6 border-b border-slate-800">
    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">System Settings</h1>
    <p class="text-xs sm:text-sm text-slate-400 mt-1">
      Admin account security, Hostinger MySQL configuration, and server-side automation.
    </p>
  </div>

  <?php if (!empty($message)): ?>
    <div class="p-3.5 rounded-xl text-xs flex items-center space-x-2 <?= $messageType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300' : 'bg-rose-500/10 border border-rose-500/30 text-rose-300' ?>">
      <span><?= e($message) ?></span>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Admin Profile Settings -->
    <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 space-y-4">
      <h2 class="text-xs font-bold text-white uppercase tracking-wider pb-2 border-b border-slate-800">
        Admin Profile Details
      </h2>
      <form method="POST" action="/blog/ai-login/settings" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
        <input type="hidden" name="action" value="update_profile">

        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1">Username (Immutable)</label>
          <input type="text" value="<?= e($adminUser['username']) ?>" disabled
            class="w-full px-3 py-2 bg-slate-900/50 border border-slate-800 rounded-lg text-slate-500 text-xs font-mono">
        </div>

        <div>
          <label for="display_name" class="block text-xs font-semibold text-slate-300 mb-1">Author Display Name</label>
          <input type="text" id="display_name" name="display_name" required value="<?= e($adminUser['display_name']) ?>"
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <div>
          <label for="email" class="block text-xs font-semibold text-slate-300 mb-1">Notification Email</label>
          <input type="email" id="email" name="email" required value="<?= e($adminUser['email']) ?>"
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <button type="submit" class="py-2.5 px-4 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow-md transition-colors">
          Save Profile
        </button>
      </form>
    </div>

    <!-- Security & Password -->
    <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 space-y-4">
      <h2 class="text-xs font-bold text-white uppercase tracking-wider pb-2 border-b border-slate-800">
        Change Password
      </h2>
      <form method="POST" action="/blog/ai-login/settings" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
        <input type="hidden" name="action" value="change_password">

        <div>
          <label for="current_password" class="block text-xs font-semibold text-slate-300 mb-1">Current Password</label>
          <input type="password" id="current_password" name="current_password" required
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <div>
          <label for="new_password" class="block text-xs font-semibold text-slate-300 mb-1">New Password (Min 8 chars)</label>
          <input type="password" id="new_password" name="new_password" required
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <div>
          <label for="confirm_password" class="block text-xs font-semibold text-slate-300 mb-1">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required
            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <button type="submit" class="py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl border border-slate-700 transition-colors">
          Update Password
        </button>
      </form>
    </div>
  </div>

  <!-- Hostinger Integration & Automation Guide -->
  <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 space-y-4">
    <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider pb-2 border-b border-slate-800 flex items-center space-x-2">
      <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      <span>Hostinger MySQL &amp; Cron Deployment Reference</span>
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-slate-400 leading-relaxed">
      <div>
        <h3 class="font-bold text-white mb-1">1. MySQL Database Connection</h3>
        <p>In <code class="text-blue-400 font-mono">blog/config.php</code>, update your Hostinger database credentials:</p>
        <pre class="bg-slate-900 p-3 rounded-lg font-mono text-[11px] text-slate-300 mt-2 overflow-x-auto"><code>define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456_blog');
define('DB_USER', 'u123456_admin');
define('DB_PASS', 'YourSecurePasswordHere');</code></pre>
      </div>

      <div>
        <h3 class="font-bold text-white mb-1">2. Scheduled Publishing Cron</h3>
        <p>In Hostinger hPanel &rarr; <strong>Advanced &rarr; Cron Jobs</strong>, add this command to run every 5 minutes:</p>
        <pre class="bg-slate-900 p-3 rounded-lg font-mono text-[11px] text-slate-300 mt-2 overflow-x-auto"><code>*/5 * * * * php /home/u123456/public_html/blog/cron.php</code></pre>
        <p class="mt-2 text-[11px] text-slate-500">Alternatively, scheduled posts are also auto-published upon any server-side database hit.</p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
