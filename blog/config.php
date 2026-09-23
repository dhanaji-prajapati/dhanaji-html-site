<?php
/**
 * Configuration for Dhanaji Prajapati SEO Blog System
 * Designed for standard Hostinger Web Hosting & Cloud Databases
 */

// Hostinger MySQL/MariaDB Credentials
// Fill in your Hostinger MySQL Database details below (or set via environment variables):
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'dhanaji_seo_blog');
define('DB_USER', getenv('DB_USER') ?: 'dhanaji_admin');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// SQLite fallback file path (used automatically during local development preview when MySQL credentials are not yet configured)
define('SQLITE_PATH', dirname(__DIR__) . '/data/blog.sqlite');

// Application URLs and Paths
define('SITE_NAME', 'Dhanaji Prajapati — Independent SEO Consultant');
define('SITE_URL', 'https://dhanajiprajapati.com');
define('BLOG_BASE_URL', '/blog');
define('ADMIN_BASE_URL', '/blog/ai-login');

// Upload directory settings
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/blog');
define('UPLOAD_URL', '/uploads/blog');
define('MAX_UPLOAD_SIZE', 8 * 1024 * 1024); // 8MB

// Allowed MIME types and extensions for blog media
define('ALLOWED_MEDIA_TYPES', [
    'image/jpeg' => ['jpg', 'jpeg'],
    'image/png'  => ['png'],
    'image/webp' => ['webp'],
    'image/avif' => ['avif'],
    'image/gif'  => ['gif'],
    'image/svg+xml' => ['svg']
]);

// Initial Admin Credentials (seeded automatically into database with password_hash() on first run)
define('DEFAULT_ADMIN_USER', 'dhanaji');
define('DEFAULT_ADMIN_EMAIL', 'dhanajiprajapati12@gmail.com');
define('DEFAULT_ADMIN_PASS', 'DhanajiSEO2026!');
