<?php
/**
 * Shared Admin Layout Navigation Header
 */
require_once __DIR__ . '/auth.php';
$adminUser = require_admin_auth();
$currentScript = basename($_SERVER['PHP_SELF']);
$currentUri = $_SERVER['REQUEST_URI'];

function is_nav_active(string $name, string $currentScript, string $currentUri): bool {
    if ($name === 'dashboard' && (strpos($currentUri, 'dashboard') !== false || $currentScript === 'dashboard.php')) return true;
    if ($name === 'posts' && (strpos($currentUri, 'posts') !== false || $currentScript === 'posts.php')) return true;
    if ($name === 'post-editor' && (strpos($currentUri, 'post-editor') !== false || $currentScript === 'post-editor.php')) return true;
    if ($name === 'categories' && (strpos($currentUri, 'categories') !== false || $currentScript === 'categories.php')) return true;
    if ($name === 'tags' && (strpos($currentUri, 'tags') !== false || $currentScript === 'tags.php')) return true;
    if ($name === 'media' && (strpos($currentUri, 'media') !== false || $currentScript === 'media.php')) return true;
    if ($name === 'settings' && (strpos($currentUri, 'settings') !== false || $currentScript === 'settings.php')) return true;
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' &middot; ' : '' ?>Blog Admin &middot; Dhanaji Prajapati</title>
  <meta name="robots" content="noindex, nofollow">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
    code, pre, .font-mono { font-family: 'JetBrains Mono', monospace; }
    [contenteditable]:empty:before {
      content: attr(data-placeholder);
      color: #94a3b8;
    }
  </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col antialiased selection:bg-blue-600 selection:text-white">

  <!-- Top Admin Bar -->
  <header class="bg-slate-950 border-b border-slate-800 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        
        <!-- Left: Logo & Portal title -->
        <div class="flex items-center space-x-4">
          <a href="/blog/ai-login/dashboard" class="flex items-center space-x-2.5 group">
            <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-md shadow-blue-500/20 group-hover:bg-blue-500 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div>
              <span class="text-sm font-bold text-white leading-none block">Dhanaji Prajapati</span>
              <span class="text-[10px] font-mono text-slate-400 leading-none block mt-0.5">Blog Admin &middot; Hostinger MySQL</span>
            </div>
          </a>

          <!-- Public site quick link -->
          <a href="/blog/" target="_blank" class="hidden md:inline-flex items-center space-x-1 text-xs text-slate-400 hover:text-white px-2.5 py-1 rounded bg-slate-800/80 border border-slate-700/60 transition-colors">
            <span>View Public Blog</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          </a>
        </div>

        <!-- Right: Fast New Post + User Profile & Logout -->
        <div class="flex items-center space-x-3">
          <a href="/blog/ai-login/post-editor" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-sm transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>New Post</span>
          </a>

          <div class="h-5 w-px bg-slate-800 hidden sm:block"></div>

          <div class="flex items-center space-x-2 text-xs">
            <span class="hidden sm:inline-block text-slate-300 font-medium"><?= e($adminUser['display_name']) ?></span>
            <a href="/blog/ai-login/logout" class="px-2.5 py-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800/80 transition-colors" title="Logout">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </a>
          </div>
        </div>

      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-slate-950/80 border-t border-slate-800/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex space-x-1 sm:space-x-2 overflow-x-auto py-2 text-xs font-medium">
          <a href="/blog/ai-login/dashboard" class="px-3 py-1.5 rounded-lg transition-colors shrink-0 <?= is_nav_active('dashboard', $currentScript, $currentUri) ? 'bg-blue-600 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
            Dashboard
          </a>
          <a href="/blog/ai-login/posts" class="px-3 py-1.5 rounded-lg transition-colors shrink-0 <?= is_nav_active('posts', $currentScript, $currentUri) ? 'bg-blue-600 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
            Manage Posts
          </a>
          <a href="/blog/ai-login/post-editor" class="px-3 py-1.5 rounded-lg transition-colors shrink-0 <?= is_nav_active('post-editor', $currentScript, $currentUri) ? 'bg-blue-600 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
            + New Post
          </a>
          <a href="/blog/ai-login/categories" class="px-3 py-1.5 rounded-lg transition-colors shrink-0 <?= is_nav_active('categories', $currentScript, $currentUri) ? 'bg-blue-600 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
            Categories
          </a>
          <a href="/blog/ai-login/tags" class="px-3 py-1.5 rounded-lg transition-colors shrink-0 <?= is_nav_active('tags', $currentScript, $currentUri) ? 'bg-blue-600 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
            Tags
          </a>
          <a href="/blog/ai-login/media" class="px-3 py-1.5 rounded-lg transition-colors shrink-0 <?= is_nav_active('media', $currentScript, $currentUri) ? 'bg-blue-600 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
            Media Library
          </a>
          <a href="/blog/ai-login/settings" class="px-3 py-1.5 rounded-lg transition-colors shrink-0 <?= is_nav_active('settings', $currentScript, $currentUri) ? 'bg-blue-600 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
            Settings
          </a>
          <a href="/blog/ai-login/logout" class="px-3 py-1.5 rounded-lg text-rose-400 hover:bg-rose-500/10 transition-colors shrink-0">
            Logout
          </a>
        </nav>
      </div>
    </div>
  </header>

  <!-- Main Content Area -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
