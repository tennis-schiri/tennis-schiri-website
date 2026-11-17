<?php
declare(strict_types=1);

/* © Tennis-Schiri: geschützter Workshop-Bereich (PHP-Session, keine JS-Passwortprüfung). */
function workshop_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    if (headers_sent()) throw new RuntimeException('HTTP-Headers bereits gesendet');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('ts_workshop_session');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => true,
        'httponly' => true, 'samesite' => 'Strict',
    ]);
    session_start();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: DENY');
}

function workshop_is_authenticated(): bool
{
    if (empty($_SESSION['workshop_authenticated']) || !is_int($_SESSION['workshop_authenticated'])) return false;
    $at = $_SESSION['workshop_authenticated'];
    $last = $_SESSION['workshop_last_seen'] ?? 0;
    if (time() - $at > 8 * 3600 || time() - (int)$last > 3 * 3600) {
        unset($_SESSION['workshop_authenticated'], $_SESSION['workshop_last_seen']);
        return false;
    }
    $_SESSION['workshop_last_seen'] = time();
    return true;
}

function workshop_require_auth(bool $redirect = true): void
{
    workshop_session_start();
    if (workshop_is_authenticated()) return;
    if ($redirect) {
        header('Location: /login.php', true, 302);
    } else {
        http_response_code(401);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Für diesen Workshop-Bereich ist eine Anmeldung erforderlich.';
    }
    exit;
}

function workshop_password_hash(): ?string
{
    // Nur der GitHub-Workflow erzeugt diesen Hash aus dem Actions-Secret.
    // Die Datei liegt im per .htaccess gesperrten _private-Ordner.
    $file = __DIR__ . '/_private/password-hash.php';
    if (!is_file($file)) return null;
    $hash = require $file;
    return is_string($hash) && $hash !== '' ? $hash : null;
}
