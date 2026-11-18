<?php
declare(strict_types=1);
require __DIR__ . '/security.php';
workshop_session_start();
$room=$_GET['room']??'';
$room=is_string($room)&&preg_match('/^[A-HJ-NP-Z2-9]{6}$/D',strtoupper($room))?strtoupper($room):'';
if (!workshop_is_authenticated()) {
    $next='/teilnehmen.php'.($room?'?room='.$room:'');
    header('Location: /login.php?next='.rawurlencode($next),true,302);
    exit;
}
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex,nofollow,noarchive">
<meta name="theme-color" content="#061c12">
<title>Workshop live verfolgen · Tennis-Schiri</title>
<link rel="icon" href="assets/logo.png">
<link rel="stylesheet" href="access.css?v=20261118-photo1">
<link rel="stylesheet" href="assets/workshop-slide.css?v=20261118-noscroll-comic03">
<link rel="stylesheet" href="assets/participant.css?v=20261118-comic01">
</head>
<body class="participant-body">
<div class="participant-app" id="participant-app">
<header class="participant-header">
  <a href="/" aria-label="Workshop-Startseite" class="participant-brand"><img src="assets/logo.png" alt="Tennis-Schiri"></a>
  <span class="participant-type">LIVE · REGELWORKSHOP</span>
  <a href="/logout.php" class="participant-logout">Abmelden</a>
</header>
<main class="participant-layout">
 <section class="join-card" aria-label="Live-Raum beitreten">
  <form id="room-form" class="room-form">
   <label for="room-code">Raumcode vom Referenten</label>
   <div class="room-inputs"><input id="room-code" name="room" inputmode="text" maxlength="6" minlength="6" autocomplete="off" spellcheck="false" placeholder="ABC234" value="<?=htmlspecialchars($room,ENT_QUOTES,'UTF-8')?>" aria-label="Sechsstelliger Raumcode">
   <button type="submit">Teilnehmen</button></div>
  </form>
  <div class="participant-controls">
   <span class="connect-status" id="connect-status" role="status">Noch nicht verbunden</span>
   <button id="follow-btn" type="button" disabled aria-pressed="true">● Live folgen</button>
  </div>
 </section>
 <section class="participant-main" aria-labelledby="participant-stage-title">
  <h1 id="participant-stage-title" class="sr-only">Workshop-Folien</h1>
  <div class="participant-stage" id="participant-stage" aria-live="polite">
   <div class="participant-placeholder"><strong>DEIN WORKSHOP.</strong><p>Gib den Raumcode ein, um die Präsentation auf deinem eigenen Gerät live zu verfolgen. Alternativ kannst du ohne Raum die Folien selbst durchblättern.</p></div>
  </div>
  <div class="participant-navigation">
   <button id="participant-prev" type="button" aria-label="Vorherige Folie">← Zurück</button>
   <span id="participant-counter">— / 136</span>
   <button id="participant-next" type="button" aria-label="Nächste Folie">Weiter →</button>
  </div>
 </section>
 <section class="participant-help">
  <h2>Mach mit.</h2>
  <p>Bei Regelfragen kannst du auf deinem Handy oder Tablet abstimmen. Die Entscheidung besprechen wir gemeinsam – die Antwort erscheint erst auf der nächsten Folie.</p>
  <p>Mit „Live folgen“ synchronisiert sich dein Gerät mit dem Referenten. Du kannst die Verbindung jederzeit pausieren und selbst durch die Folien blättern.</p>
 </section>
</main>
<footer class="participant-footer"><span>© Tennis-Schiri · Regelworkshop</span><a href="/">Workshop-Startseite</a></footer>
</div>
<script src="assets/workshop-slide.js?v=20261118-noscroll-comic03" defer></script>
<script src="assets/participant.js?v=20261118-comic01" defer></script>
</body></html>
