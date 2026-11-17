<?php
declare(strict_types=1);
require __DIR__ . '/security.php';
workshop_session_start();
if (workshop_is_authenticated()) {
    header('Location: /presentation.php', true, 302);
    exit;
}
if (empty($_SESSION['workshop_csrf'])) {
    $_SESSION['workshop_csrf'] = bin2hex(random_bytes(24));
}
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = $_POST['csrf'] ?? '';
    $password = $_POST['password'] ?? '';
    $until = (int)($_SESSION['workshop_lock_until'] ?? 0);
    if ($until > time()) {
        $error = 'Zu viele Versuche. Bitte in einigen Minuten erneut versuchen.';
    } elseif (!is_string($sent) || !hash_equals($_SESSION['workshop_csrf'], $sent)) {
        http_response_code(400);
        $error = 'Das Formular ist abgelaufen. Bitte die Seite neu laden.';
    } else {
        $hash = workshop_password_hash();
        // Timingunabhängiges Verhalten auch bei fehlender Konfiguration.
        $verified = is_string($password) && $hash !== null && password_verify($password, $hash);
        if ($verified) {
            session_regenerate_id(true);
            $_SESSION['workshop_authenticated'] = time();
            $_SESSION['workshop_last_seen'] = time();
            unset($_SESSION['workshop_failures'], $_SESSION['workshop_lock_until'], $_SESSION['workshop_csrf']);
            header('Location: /presentation.php', true, 303);
            exit;
        }
        $_SESSION['workshop_failures'] = (int)($_SESSION['workshop_failures'] ?? 0) + 1;
        if ($_SESSION['workshop_failures'] >= 5) {
            $_SESSION['workshop_failures'] = 0;
            $_SESSION['workshop_lock_until'] = time() + 15 * 60;
        }
        $error = 'Passwort nicht erkannt. Bitte erneut versuchen.';
    }
}
?>
<!doctype html><html lang="de"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive">
<meta name="theme-color" content="#071710"><title>Workshop-Zugang · Tennis-Schiri</title>
<link rel="icon" href="assets/logo.png"><link rel="stylesheet" href="access.css?v=20261117-2"></head><body><div class="site">
<header class="mast wrap"><img class="brand" src="assets/logo.png" alt="Tennis-Schiri"><span class="mast-mark">Geschützter Bereich</span></header>
<main class="login-layout wrap"><section class="login-card" aria-labelledby="heading">
<p class="eyebrow">Tennis-Schiri · Regelworkshop</p><h1 id="heading">BEREIT FÜR <span style="color:var(--lime)">DEN WORKSHOP?</span></h1>
<p>Gib das Workshop-Passwort ein. Danach kannst du die vollständige Präsentation mit 136 Folien, Videos und Regelfragen öffnen.</p>
<?php if ($error !== null): ?><div class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?>
<form method="post" action="/login.php" autocomplete="off">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['workshop_csrf'], ENT_QUOTES, 'UTF-8') ?>">
<div class="form-row"><label for="password">Workshop-Passwort</label><input type="password" id="password" name="password" placeholder="Passwort eingeben" autocomplete="current-password" autofocus required minlength="1"></div>
<button class="btn" type="submit">Präsentation öffnen ↗</button></form>
<a class="backlink" href="/">← Zur Workshop-Startseite</a>
</section></main>
<footer class="footer wrap"><div class="footer-brand">TENNIS-SCHIRI · REGELWORKSHOP</div><div class="footer-backend"><strong>Backend</strong><a href="/">Startseite</a><a href="https://tennis-schiri.de/" target="_blank" rel="noopener noreferrer">Tennis-Schiri.de</a></div></footer>
</div></body></html>
