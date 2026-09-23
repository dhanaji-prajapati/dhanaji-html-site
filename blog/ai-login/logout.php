<?php
require_once __DIR__ . '/auth.php';
logout_admin();
header('Location: /blog/ai-login?logged_out=1');
exit;
