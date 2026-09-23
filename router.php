<?php
// router.php - High-performance local development router for PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If request has a file extension and is a real file (e.g. .css, .js, .svg, .png, .jpg, .webp, .ico)
// let the built-in server handle it directly, UNLESS it is a .php file
$ext = pathinfo($uri, PATHINFO_EXTENSION);
if (!empty($ext) && $ext !== 'php' && $ext !== 'html') {
    $filePath = __DIR__ . $uri;
    if (file_exists($filePath)) {
        return false;
    }
}

// Route Admin Endpoints
if (preg_match('#^/blog/ai-login/dashboard/?$#', $uri)) {
    require __DIR__ . '/blog/ai-login/dashboard.php';
    exit;
}

if (preg_match('#^/blog/ai-login/posts/?$#', $uri)) {
    require __DIR__ . '/blog/ai-login/posts.php';
    exit;
}

if (preg_match('#^/blog/ai-login/post-editor/?$#', $uri)) {
    require __DIR__ . '/blog/ai-login/post-editor.php';
    exit;
}

if (preg_match('#^/blog/ai-login/categories/?$#', $uri)) {
    require __DIR__ . '/blog/ai-login/categories.php';
    exit;
}

if (preg_match('#^/blog/ai-login/tags/?$#', $uri)) {
    require __DIR__ . '/blog/ai-login/tags.php';
    exit;
}

if (preg_match('#^/blog/ai-login/media/?$#', $uri)) {
    require __DIR__ . '/blog/ai-login/media.php';
    exit;
}

if (preg_match('#^/blog/ai-login/settings/?$#', $uri)) {
    require __DIR__ . '/blog/ai-login/settings.php';
    exit;
}

if (preg_match('#^/blog/ai-login/logout/?$#', $uri)) {
    require __DIR__ . '/blog/ai-login/logout.php';
    exit;
}

if (preg_match('#^/blog/ai-login/?$#', $uri)) {
    require __DIR__ . '/blog/ai-login/index.php';
    exit;
}

// Route Public Blog Root
if (preg_match('#^/blog/?$#', $uri)) {
    require __DIR__ . '/blog/index.php';
    exit;
}

// Route Single Blog Articles (/blog/article-slug/ or /blog/article-slug)
if (preg_match('#^/blog/([a-zA-Z0-9_-]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/blog/index.php';
    exit;
}

// For all other static HTML pages (e.g. /, /seo-services/, /pricing/, etc.)
$filePath = __DIR__ . $uri;
if ($uri !== '/' && is_dir($filePath)) {
    $indexHtml = rtrim($filePath, '/') . '/index.html';
    if (file_exists($indexHtml)) {
        require $indexHtml;
        exit;
    }
} elseif (file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// Root page
if ($uri === '/') {
    require __DIR__ . '/index.html';
    exit;
}

return false;
