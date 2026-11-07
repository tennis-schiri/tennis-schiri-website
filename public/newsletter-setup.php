<?php
declare(strict_types=1);
/* One-time, email-verified newsletter installer. Stores configuration and SQLite OUTSIDE the webroot. */
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
if (($_SERVER['HTTPS'] ?? '') !== 'on' && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https') {
 http_response_code(403); exit('Die Einrichtung ist ausschließlich über HTTPS erreichbar.');
}
$root=realpath(__DIR__);
$base=realpath(dirname(__DIR__));
if (!$root || !$base) { http_response_code(503); exit('Webverzeichnis nicht verfügbar.'); }
$configFile=$base.'/tennis-schiri-newsletter-config.php';
$dataDir=$base.'/tennis-schiri-newsletter-data';
$challengeFile=$dataDir.'/.setup-challenge';
$dbPath=$dataDir.'/tennis-schiri-newsletter.sqlite';
$mailbox='news@tennis-schiri.de';
$adminMailbox='mw@tennis-schiri.de';
function screen(string $title,string $body,int $status=200): never {
 http_response_code($status);
 echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>'.htmlspecialchars($title,ENT_QUOTES,'UTF-8').' · Tennis-Schiri Newsletter</title><style>
 :root{color-scheme:light}*{box-sizing:border-box}body{margin:0;background:#ecf1e5;color:#18382b;font:15px/1.6 system-ui}.wrap{max-width:650px;margin:8vh auto;padding:35px;background:#fff;border-radius:20px;box-shadow:0 18px 55px #173d3020}h1{font-size:30px;letter-spacing:-.04em;line-height:1.15}input{display:block;width:100%;box-sizing:border-box;font:inherit;padding:13px;margin:8px 0 16px;border:1px solid #a5bba9;border-radius:10px}button{font:inherit;cursor:pointer;border:0;border-radius:10px;padding:13px 18px;background:#173d30;color:white;font-weight:800}a{color:#205a3b}small{color:#698071}code{overflow-wrap:anywhere}</style><main class="wrap"><a href="/" aria-label="Tennis-Schiri Startseite"><img src="/assets/branding/ts-logo-hell.png?v=20261107-3" alt="Tennis-Schiri" width="260" height="53" style="display:block;width:260px;max-width:100%;height:auto;margin-bottom:19px"></a><p><small>NEWSLETTER-EINRICHTUNG</small></p><h1>'.htmlspecialchars($title,ENT_QUOTES,'UTF-8').'</h1>'.$body.'<p><a href="/newsletter/">Zur Newsletter-Seite ↗</a></p></main></html>';
 exit;
}
if (is_file($configFile)) {
 try { $already=require $configFile; }
 catch (Throwable $e) { error_log('TS newsletter setup invalid config: '.$e->getMessage()); screen('Konfiguration prüfen','<p>Die private Konfigurationsdatei konnte nicht gelesen werden.</p>',503); }
 if (is_array($already) && ($already['enabled'] ?? false)===true) {
  screen('Bereits eingerichtet','<p>Die Newsletter-Funktion ist eingerichtet. Der Einrichtungsassistent ist gesperrt.</p><p><a href="/newsletter-admin.php">Zur geschützten Verwaltung ↗</a></p>',403);
 }
 screen('Bereits vorbereitet','<p>Eine private Konfigurationsdatei liegt bereits vor. Sie wird zum Schutz der bestehenden Konfiguration nicht überschrieben.</p>',403);
}
if (!is_dir($dataDir) && !@mkdir($dataDir,0700)) {
 screen('Privaten Datenordner vorbereiten','<p>PHP kann neben dem öffentlichen Website-Ordner derzeit keinen Ordner anlegen. Bitte beim Hoster einen Ordner <code>tennis-schiri-newsletter-data</code> <strong>neben</strong> dem öffentlichen Verzeichnis <code>tennis-schiri-live</code> anlegen und für PHP beschreibbar machen. Anschließend diese Seite erneut öffnen.</p>',503);
}
$realData=realpath($dataDir);
if (!$realData || str_starts_with($realData.DIRECTORY_SEPARATOR,$root.DIRECTORY_SEPARATOR)) {
 screen('Speicherort ungeeignet','<p>Der Newsletter-Speicher muss außerhalb des öffentlichen Webverzeichnisses liegen.</p>',503);
}
if (!is_writable($dataDir)) screen('Datenordner nicht beschreibbar','<p>Der private Datenordner ist für PHP nicht beschreibbar. Bitte beim Hosting die Berechtigungen prüfen. Keine pauschalen 777-Berechtigungen setzen.</p>',503);
$challenge=null;
if (is_file($challengeFile)) {
 $raw=@file_get_contents($challengeFile);
 $challenge=is_string($raw)?json_decode($raw,true):null;
 if (!is_array($challenge)) $challenge=null;
}
$now=time();
$action=(string)($_POST['action']??'');
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST' && $action==='send') {
 if ($challenge && ($challenge['created']??0)>$now-900) screen('Bestätigung bereits angefordert','<p>Es wurde vor weniger als 15 Minuten eine Einrichtungs-E-Mail angefordert. Prüfe dein Postfach und gegebenenfalls den Spamordner.</p>',429);
 $token=bin2hex(random_bytes(32));
 $challenge=['hash'=>hash('sha256',$token),'created'=>$now,'expires'=>$now+1800];
 if (@file_put_contents($challengeFile,json_encode($challenge,JSON_THROW_ON_ERROR),LOCK_EX)===false) screen('Speicherfehler','<p>Der Bestätigungscode konnte nicht gespeichert werden.</p>',503);
 @chmod($challengeFile,0600);
 $link='https://tennis-schiri.de/newsletter-setup.php?token='.$token;
 $headers=['From: Tennis-Schiri <'.$mailbox.'>','Reply-To: '.$mailbox,'Content-Type: text/plain; charset=UTF-8','MIME-Version: 1.0'];
 $sent=mail($adminMailbox,'Tennis-Schiri Newsletter: Einrichtung bestaetigen',"Bitte diesen Link innerhalb von 30 Minuten öffnen, um die Newsletter-Einrichtung fortzusetzen:\n\n".$link."\n\nFalls du die Einrichtung nicht angestoßen hast, ignoriere diese Nachricht.",implode("\r\n",$headers),'-f'.$mailbox);
 if (!$sent) { @unlink($challengeFile);screen('Mailversand fehlgeschlagen','<p>Der Server konnte keine Nachricht an die hinterlegte Adresse senden. Bitte die PHP-Mailfunktion und das Absenderpostfach prüfen.</p>',503); }
 screen('Bestätigung verschickt','<p>Ein Einrichtungslink wurde an das bereits für Tennis-Schiri verwendete Postfach gesendet. Er ist 30 Minuten gültig.</p><p>Öffne den Link aus der E-Mail und wähle anschließend ein Admin-Passwort.</p>');
}
$token=(string)($_POST['token']??$_GET['token']??'');
$authorized=$challenge && preg_match('/^[a-f0-9]{64}$/D',$token) && (int)($challenge['expires']??0)>=$now && hash_equals((string)$challenge['hash'],hash('sha256',$token));
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST' && $action==='finish') {
 if (!$authorized) screen('Einrichtungslink ungültig','<p>Der Link ist abgelaufen oder wurde bereits benutzt. Bitte eine neue Bestätigung anfordern.</p>',403);
 $password=(string)($_POST['password']??'');
 $check=(string)($_POST['repeat']??'');
 if (strlen($password)<16||strlen($password)>200||!hash_equals($password,$check)) screen('Passwort prüfen','<p>Das Passwort muss mindestens 16 Zeichen haben und in beiden Feldern identisch sein.</p>',422);
 if (!class_exists('PDO')||!in_array('sqlite',PDO::getAvailableDrivers(),true)) screen('SQLite nicht verfügbar','<p>Für den Newsletter wird PHP PDO_SQLITE benötigt.</p>',503);
 try {
  $db=new PDO('sqlite:'.$dbPath,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $db->exec('PRAGMA busy_timeout=5000');
  $db->exec('CREATE TABLE IF NOT EXISTS subscribers (id INTEGER PRIMARY KEY,email TEXT NOT NULL UNIQUE,role TEXT NOT NULL,interests TEXT NOT NULL,consent_version TEXT NOT NULL,created_at INTEGER NOT NULL,confirmed_at INTEGER,confirm_hash TEXT,unsubscribe_hash TEXT,expires_at INTEGER NOT NULL)');
  $db->exec('CREATE TABLE IF NOT EXISTS attempts (ip_hash TEXT NOT NULL,created_at INTEGER NOT NULL)');
  $db->exec('CREATE TABLE IF NOT EXISTS newsletter_admin_login_attempts (ip_hash TEXT NOT NULL,attempted_at INTEGER NOT NULL)');
  $db->exec("CREATE TABLE IF NOT EXISTS newsletter_campaigns (id INTEGER PRIMARY KEY AUTOINCREMENT,subject TEXT NOT NULL,body TEXT NOT NULL,topic TEXT NOT NULL,state TEXT NOT NULL DEFAULT 'draft',created_at INTEGER NOT NULL,updated_at INTEGER NOT NULL)");
  $db->exec("CREATE TABLE IF NOT EXISTS newsletter_deliveries (campaign_id INTEGER NOT NULL,subscriber_id INTEGER NOT NULL,recipient_key TEXT NOT NULL,state TEXT NOT NULL DEFAULT 'pending',attempted_at INTEGER,PRIMARY KEY(campaign_id,subscriber_id))");
  unset($db);
  @chmod($dbPath,0600);
 } catch(Throwable $e) {error_log('TS newsletter setup SQLite: '.$e->getMessage());screen('Datenbankfehler','<p>Der private Speicher kann nicht initialisiert werden.</p>',503);}
 $key=bin2hex(random_bytes(32));
 $hash=password_hash($password,PASSWORD_DEFAULT);
 $content="<?php\nreturn [\n 'enabled' => true,\n 'setup_complete' => true,\n 'database' => __DIR__ . '/tennis-schiri-newsletter-data/tennis-schiri-newsletter.sqlite',\n 'key' => ".var_export($key,true).",\n 'admin_password_hash' => ".var_export($hash,true).",\n];\n";
 $file=@fopen($configFile,'x');
 if (!$file) screen('Private Konfiguration nicht beschreibbar','<p>Der Server darf keine Datei neben dem öffentlichen Website-Ordner anlegen. Bitte die Verzeichnisberechtigung durch den Hoster prüfen; keine Konfiguration im öffentlichen Ordner ablegen.</p>',503);
 $written=@fwrite($file,$content);
 @fclose($file);
 if ($written===false || $written!==strlen($content)) { @unlink($configFile);screen('Konfiguration konnte nicht gespeichert werden','<p>Bitte Berechtigungen beim Hoster prüfen.</p>',503); }
 @chmod($configFile,0600);
 @unlink($challengeFile);
 screen('Newsletter eingerichtet','<p>Die Konfiguration und die Abonnentendatenbank liegen außerhalb des öffentlichen Webordners. Die Einrichtung ist nun gesperrt.</p><p><a href="/newsletter-admin.php">Newsletter-Verwaltung öffnen ↗</a></p>');
}
if ($authorized) {
 screen('Admin-Zugang festlegen','<p>Die Kontrolle über das E-Mail-Postfach wurde bestätigt. Lege jetzt ein eigenes Passwort für die Newsletter-Verwaltung fest.</p><form method="post" action="/newsletter-setup.php"><input type="hidden" name="action" value="finish"><input type="hidden" name="token" value="'.htmlspecialchars($token,ENT_QUOTES,'UTF-8').'"><label>Admin-Passwort (mindestens 16 Zeichen)<input type="password" name="password" required minlength="16" maxlength="200" autocomplete="new-password"></label><label>Passwort wiederholen<input type="password" name="repeat" required minlength="16" maxlength="200" autocomplete="new-password"></label><button type="submit">Newsletter sicher einrichten</button></form>');
}
screen('Newsletter-Einrichtung','<p>Die Newsletter-Funktion wird auf dem eigenen Hosting eingerichtet. Die Bestätigung läuft über das Tennis-Schiri-Postfach. Es werden keine Zugangsdaten in GitHub gespeichert.</p><form method="post" action="/newsletter-setup.php"><input type="hidden" name="action" value="send"><button type="submit">Einrichtung per E-Mail bestätigen</button></form><p><small>Die Funktion benötigt PHP 8.1+, PDO_SQLITE, eine funktionierende PHP-Mailfunktion und Schreibrechte auf einen privaten Ordner außerhalb des Webroots.</small></p>');
