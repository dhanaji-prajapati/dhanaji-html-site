<?php
/**
 * Hostinger Cron Job Script for Scheduled Blog Posts
 * Can be run from Hostinger cPanel / hPanel Cron Jobs:
 * Example: * * * * * php /home/USERNAME/public_html/blog/cron.php
 * Or accessed via secure HTTP token: /blog/cron.php?token=DHANAJI_CRON_SECRET
 */

require_once __DIR__ . '/db.php';

// Verify token if accessed via web
if (php_sapi_name() !== 'cli') {
    $token = $_GET['token'] ?? '';
    $cronSecret = getenv('CRON_SECRET') ?: 'dhanaji_secure_cron_2026';
    if (!hash_equals($cronSecret, $token)) {
        http_response_code(403);
        die("Forbidden");
    }
}

$pdo = get_db();
$publishedCount = run_scheduled_publisher($pdo);

$response = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'posts_published' => $publishedCount
];

if (php_sapi_name() === 'cli') {
    echo "[" . date('Y-m-d H:i:s') . "] Blog Cron Run: {$publishedCount} scheduled post(s) published.\n";
} else {
    header('Content-Type: application/json');
    echo json_encode($response);
}
