<?php
// router.php
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Nếu file tồn tại, trả file bình thường
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Nếu không, chuyển tất cả request về index.php
require_once __DIR__ . '/index.php';
