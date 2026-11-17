<?php
declare(strict_types=1);
require __DIR__ . '/security.php';
workshop_require_auth();
header('Content-Type: text/html; charset=utf-8');
header("Content-Security-Policy: default-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; media-src 'self' blob:; connect-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'");
$source = __DIR__ . '/_private/index.html';
if (!is_file($source)) {
    http_response_code(503);
    echo 'Die Präsentation wurde noch nicht bereitgestellt.';
    exit;
}
readfile($source);
