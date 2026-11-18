<?php
declare(strict_types=1);
require __DIR__ . '/security.php';
workshop_require_auth(false);

$path = $_GET['path'] ?? '';
if (!is_string($path) || !preg_match('~^(?:slides/slide-[0-9]{3}\.webp|media/clip-[0-9]{3}\.mp4|comics/(?:hc0[12]|jeu-de-paume-original-comic|wingfield-original-comic|wimbledon-1877-original-comic|lawn-tennis-1887-original-comic)\.webp|history/(?:jeu-de-paume-original|wingfield-original|wimbledon-1877-original|lawn-tennis-1887-original)\.jpg|slides\.json)$~D', $path)) {
    http_response_code(404);
    exit;
}
$private = realpath(__DIR__ . '/_private');
// Optionale Originalvideos in besserer Auflösung (eigener geschützter FTP-Ordner).
// Fehlt ein HQ-Clip, verwenden wir die kompakte H.264/MP4-Fassung aus dem Build.
$source = __DIR__ . '/_private/' . $path;
if (str_starts_with($path, 'media/')) {
    $hq = __DIR__ . '/_private/media-hq/' . basename($path);
    if (is_file($hq)) $source = $hq;
}
$file = realpath($source);
if ($private === false || $file === false || !str_starts_with($file, $private . DIRECTORY_SEPARATOR) || !is_file($file)) {
    http_response_code(404);
    exit;
}
$type = str_ends_with($path, '.mp4') ? 'video/mp4'
    : (str_ends_with($path, '.webp') ? 'image/webp' : (str_ends_with($path, '.jpg') ? 'image/jpeg' : 'application/json; charset=utf-8'));
header('Content-Type: ' . $type);
header('X-Content-Type-Options: nosniff');
header('Accept-Ranges: bytes');
header('Content-Disposition: inline');

$size = filesize($file);
if ($size === false) {
    http_response_code(500);
    exit;
}
$start = 0;
$end = $size - 1;
$range = $_SERVER['HTTP_RANGE'] ?? '';
if (is_string($range) && $range !== '') {
    if (!preg_match('/^bytes=(\d*)-(\d*)$/D', $range, $m)) {
        header('Content-Range: bytes */' . $size);
        http_response_code(416);
        exit;
    }
    // Unterstützt auch Suffix-Ranges: bytes=-500
    if ($m[1] === '' && $m[2] !== '') {
        $start = max(0, $size - (int)$m[2]);
    } else {
        $start = $m[1] !== '' ? (int)$m[1] : 0;
        $end = $m[2] !== '' ? min($size - 1, (int)$m[2]) : $size - 1;
    }
    if ($start > $end || $start >= $size) {
        header('Content-Range: bytes */' . $size);
        http_response_code(416);
        exit;
    }
    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}
header('Content-Length: ' . ($end - $start + 1));
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD') exit;
session_write_close();
$stream = fopen($file, 'rb');
if ($stream === false) exit;
fseek($stream, $start);
$remaining = $end - $start + 1;
while ($remaining > 0 && !feof($stream)) {
    $chunk = fread($stream, min($remaining, 64 * 1024));
    if ($chunk === false || $chunk === '') break;
    echo $chunk;
    $remaining -= strlen($chunk);
    if (connection_aborted()) break;
}
fclose($stream);
