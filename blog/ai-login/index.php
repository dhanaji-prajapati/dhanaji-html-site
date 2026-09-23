<?php
require_once __DIR__ . '/auth.php';

// Redirect if already authenticated
if (is_admin_logged_in()) {
    header('Location: /blog/ai-login/dashboard');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both your username/email and password.';
    } else {
        if (login_admin($username, $password)) {
            header('Location: /blog/ai-login/dashboard');
            exit;
        } else {
            // Anti-brute force delay
            usleep(250000); // 250ms
            $error = 'Invalid credentials. Please verify and try again.';
        }
    }
}

$csrf_token = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog Management Portal &middot; Dhanaji Prajapati</title>
  <meta name="robots" content="noindex, nofollow">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
    code, pre, .font-mono { font-family: 'JetBrains Mono', monospace; }
  </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 selection:bg-blue-600 selection:text-white">

  <div class="w-full max-w-md">
    <!-- Brand badge -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-500/20 mb-4 ring-1 ring-white/20">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>
      <h1 class="text-2xl font-extrabold text-white tracking-tight">Blog Admin Access</h1>
      <p class="text-xs text-slate-400 mt-1.5 font-mono">Dhanaji Prajapati &middot; Content Management System</p>
    </div>

    <!-- Login Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-sm">
      <?php if (!empty($error)): ?>
        <div class="mb-6 p-3.5 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-300 text-xs flex items-center space-x-2">
          <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="/blog/ai-login" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

        <div>
          <label for="username" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
            Username or Email
          </label>
          <div class="relative">
            <input type="text" id="username" name="username" required autofocus
              value="<?= e($_POST['username'] ?? '') ?>"
              placeholder="e.g. dhanaji or email"
              class="w-full px-4 py-3 bg-slate-950/80 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-2">
            <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
              Password
            </label>
          </div>
          <div class="relative">
            <input type="password" id="password" name="password" required
              placeholder="••••••••••••"
              class="w-full px-4 py-3 bg-slate-950/80 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
          </div>
        </div>

        <button type="submit"
          class="w-full mt-2 py-3 px-4 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center space-x-2">
          <span>Authenticate &amp; Enter</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
      </form>

      <div class="mt-6 pt-5 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500">
        <span class="flex items-center space-x-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
          <span>PHP 8 &middot; PDO MySQL / MariaDB</span>
        </span>
        <a href="/" class="text-slate-400 hover:text-white transition-colors">&larr; Return to Website</a>
      </div>
    </div>

    <div class="text-center mt-6 text-xs text-slate-600">
      &copy; <?= date('Y') ?> Dhanaji Prajapati &middot; Private Administrative System
    </div>
  </div>

</body>
</html>
