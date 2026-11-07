<?php
declare(strict_types=1);
/* Tennis-Schiri Newsletter: staged; requires private config outside public webroot. */
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'; form-action \'self\'; base-uri \'none\'; frame-ancestors \'none\'');
function page(string $title,string $body,int $code=200): never {
 http_response_code($code);header('Content-Type: text/html; charset=UTF-8');
 echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.htmlspecialchars($title,ENT_QUOTES). ' | Tennis-Schiri</title><style>body{font:16px/1.65 system-ui;background:#eff4e8;color:#173d2d;margin:0}.wrap{max-width:620px;margin:9vh auto;padding:36px;background:white;border-radius:18px}a{color:#2a6844}button{background:#173d2d;color:white;border:0;padding:12px 20px;border-radius:8px;cursor:pointer}</style><main class="wrap"><h1>'.htmlspecialchars($title,ENT_QUOTES).'</h1>'.$body.'<p><a href="/">Zur Tennis-Schiri-Startseite</a></p></main></html>';exit;
}
function config(): array {
 $path=dirname(__DIR__).'/tennis-schiri-newsletter-config.php';
 if(!is_file($path)) page('Newsletter noch nicht freigeschaltet','<p>Die Anmeldung ist in Vorbereitung.</p>',503);
 $cfg=require $path;
 if(!is_array($cfg) || ($cfg['enabled']??false)!==true || empty($cfg['database']) || empty($cfg['key'])) page('Newsletter noch nicht freigeschaltet','<p>Die Anmeldung ist in Vorbereitung.</p>',503);
 $realRoot=realpath(__DIR__);$folder=realpath(dirname($cfg['database']));
 if(!$folder || !$realRoot || str_starts_with($folder.DIRECTORY_SEPARATOR,$realRoot.DIRECTORY_SEPARATOR)) page('Konfigurationsfehler','<p>Privater Speicher nicht verfügbar.</p>',503);
 return $cfg;
}
function db(array $cfg): PDO {
 try{
  $db=new PDO('sqlite:'.$cfg['database'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  $db->exec('PRAGMA busy_timeout=5000');
  $db->exec('CREATE TABLE IF NOT EXISTS subscribers (id INTEGER PRIMARY KEY,email TEXT NOT NULL UNIQUE,role TEXT NOT NULL,interests TEXT NOT NULL,consent_version TEXT NOT NULL,created_at INTEGER NOT NULL,confirmed_at INTEGER,confirm_hash TEXT,unsubscribe_hash TEXT,expires_at INTEGER NOT NULL)');
  $db->exec('CREATE TABLE IF NOT EXISTS attempts (ip_hash TEXT NOT NULL,created_at INTEGER NOT NULL)');
  return $db;
 }catch(Throwable $e){error_log('Tennis-Schiri Newsletter DB error: '.$e->getMessage());page('Vorübergehend nicht verfügbar','<p>Bitte versuche es später noch einmal.</p>',503);}
}
function token(): string {return bin2hex(random_bytes(32));}
function hashed(string $s): string {return hash('sha256',$s);}
function mailLink(array $cfg,string $email,string $subject,string $body): bool {
 if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>254 || preg_match('/[\r\n]/',$email)) return false;
 $subject='=?UTF-8?B?'.base64_encode($subject).'?=';
 $sender='news@tennis-schiri.de';
 $headers=['From: Tennis-Schiri Newsletter <'.$sender.'>','Reply-To: '.$sender,'MIME-Version: 1.0','Content-Type: text/plain; charset=UTF-8','X-Auto-Response-Suppress: All'];
 return mail($email,$subject,$body,implode("\r\n",$headers),'-f'.$sender);
}
$cfg=config();$db=db($cfg);$method=$_SERVER['REQUEST_METHOD']??'GET';$action=$_POST['action']??$_GET['action']??'';
$now=time();
$db->prepare('DELETE FROM subscribers WHERE confirmed_at IS NULL AND expires_at < ?')->execute([$now]);
$db->prepare('DELETE FROM attempts WHERE created_at < ?')->execute([$now-86400]);
if($action==='join' && $method==='POST'){
 if(!empty($_POST['website'])) page('Vielen Dank','<p>Bitte prüfe dein Postfach.</p>');
 $origin=$_SERVER['HTTP_ORIGIN']??'';
 if($origin!=='' && $origin!=='https://tennis-schiri.de' && $origin!=='https://www.tennis-schiri.de') page('Ungültige Anfrage','<p>Bitte nutze das Formular auf tennis-schiri.de.</p>',403);
 $email=strtolower(trim((string)($_POST['email']??'')));
 $role=(string)($_POST['role']??'sonstige');$allowed=['verein','veranstalter','trainer','spieler','sonstige'];
 $interest=$_POST['interests']??[];if(!is_array($interest))$interest=[];
 $interest=array_values(array_intersect(['turniere','regeln','workshops'],array_map('strval',$interest)));
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||!in_array($role,$allowed,true)||empty($interest)||($_POST['consent']??'')!=='yes')page('Angaben prüfen','<p>Bitte überprüfe E-Mail-Adresse, Interessen und Einwilligung.</p>',422);
 $ip=hashed(($cfg['key']).'|'.($_SERVER['REMOTE_ADDR']??'unknown'));
 $q=$db->prepare('SELECT COUNT(*) FROM attempts WHERE ip_hash=? AND created_at>?');$q->execute([$ip,$now-3600]);
 if((int)$q->fetchColumn()>=6)page('Zu viele Versuche','<p>Bitte versuche es später erneut.</p>',429);
 $db->prepare('INSERT INTO attempts(ip_hash,created_at) VALUES(?,?)')->execute([$ip,$now]);
 $q=$db->prepare('SELECT confirmed_at FROM subscribers WHERE email=?');$q->execute([$email]);$old=$q->fetch();
 if($old && $old['confirmed_at']!==null) page('Vielen Dank','<p>Wenn diese Adresse bereits bestätigt ist, bleibt ihre bestehende Anmeldung unverändert.</p>');
 $confirm=token();$unsubscribe=token();
 $q=$db->prepare('INSERT INTO subscribers(email,role,interests,consent_version,created_at,confirmed_at,confirm_hash,unsubscribe_hash,expires_at) VALUES(?,?,?,?,?,NULL,?,?,?) ON CONFLICT(email) DO UPDATE SET role=excluded.role,interests=excluded.interests,consent_version=excluded.consent_version,created_at=excluded.created_at,confirm_hash=excluded.confirm_hash,unsubscribe_hash=excluded.unsubscribe_hash,expires_at=excluded.expires_at');
 $q->execute([$email,$role,json_encode($interest,JSON_THROW_ON_ERROR), 'ts-newsletter-2026-11', $now,hashed($confirm),hashed($unsubscribe),$now+14*86400]);
 $link='https://tennis-schiri.de/newsletter.php?action=confirm&token='.$confirm;
 $body="Bitte bestätige deine Anmeldung zum Tennis-Schiri-Newsletter:\n\n".$link."\n\nFalls du dich nicht angemeldet hast, ignoriere diese Nachricht. Der Link verfällt nach 14 Tagen.\nKontakt: news@tennis-schiri.de";
 if(!mailLink($cfg,$email,'Tennis-Schiri Newsletter: Anmeldung bestaetigen',$body)){error_log('Tennis-Schiri Newsletter: mail() failed');page('Versand derzeit nicht möglich','<p>Die Bestätigungsmail konnte nicht versendet werden. Bitte versuche es später erneut.</p>',503);}
 page('Bitte E-Mail bestätigen','<p>Wir haben dir einen Bestätigungslink geschickt. Schau bei Bedarf auch in den Spamordner.</p>');
}
if($action==='confirm' && $method==='GET'){
 $t=(string)($_GET['token']??'');if(!preg_match('/^[a-f0-9]{64}$/',$t))page('Link ungültig','<p>Dieser Bestätigungslink ist ungültig.</p>',400);
 $q=$db->prepare('SELECT id,email,unsubscribe_hash FROM subscribers WHERE confirm_hash=? AND confirmed_at IS NULL AND expires_at>=?');$q->execute([hashed($t),$now]);$r=$q->fetch();
 if(!$r)page('Link abgelaufen','<p>Dieser Link ist ungültig oder bereits verwendet.</p>',400);
 $db->prepare('UPDATE subscribers SET confirmed_at=?,confirm_hash=NULL,expires_at=? WHERE id=?')->execute([$now,2147483647,$r['id']]);
 $notification="Neue bestätigte Anmeldung zum Tennis-Schiri-Newsletter.\n\nE-Mail: ".$r['email']."\n\nÖffne die Newsletter-Verwaltung: https://tennis-schiri.de/newsletter-admin.php\n\nDiese Benachrichtigung wird erst nach erfolgreichem Double-Opt-in versendet.";
 if(!mailLink($cfg,'mw@tennis-schiri.de','Tennis-Schiri Newsletter: Neue bestaetigte Anmeldung',$notification)) {
  error_log('Tennis-Schiri Newsletter: admin signup notification not accepted by mail()');
 }
 page('Anmeldung bestätigt','<p>Deine Anmeldung zum Tennis-Schiri-Newsletter ist bestätigt. Du erhältst nur Informationen zu deinen ausgewählten Themen.</p><p>Du kannst dich jederzeit <a href="/newsletter.php?action=unsubscribe-request">online abmelden</a> oder eine E-Mail an news@tennis-schiri.de schreiben.</p>');
}
if($action==='unsubscribe-signed' && ($method==='GET'||$method==='POST')){
 $id=filter_var(($method==='POST'?$_POST['id']:$_GET['id'])??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
 $sig=(string)(($method==='POST'?$_POST['sig']:$_GET['sig'])??'');
 if(!$id||!preg_match('/^[a-f0-9]{64}$/D',$sig))page('Link ungültig','<p>Dieser Abmeldelink ist ungültig.</p>',400);
 $q=$db->prepare('SELECT id,email FROM subscribers WHERE id=? AND confirmed_at IS NOT NULL');
 $q->execute([$id]);$subscriber=$q->fetch();
 if(!$subscriber || !hash_equals(hash_hmac('sha256',$id.'|'.$subscriber['email'],$cfg['key']),$sig))
  page('Link ungültig','<p>Der Abmeldelink ist ungültig oder nicht mehr aktuell.</p>',400);
 if($method==='GET'){
  page('Vom Newsletter abmelden','<p>Du kannst deine Tennis-Schiri Newsletter-Anmeldung hier vollständig löschen.</p><form method="post" action="/newsletter.php"><input type="hidden" name="action" value="unsubscribe-signed"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="sig" value="'.htmlspecialchars($sig,ENT_QUOTES).'"><button type="submit">Anmeldung endgültig löschen</button></form>');
 }
 $db->prepare('DELETE FROM subscribers WHERE id=? AND email=?')->execute([$id,$subscriber['email']]);
 page('Abgemeldet','<p>Deine Anmeldung wurde gelöscht. Du erhältst keine weiteren Newsletter-E-Mails.</p>');
}
if($action==='unsubscribe-request' && $method==='GET'){
 page('Abmeldung anfordern','<p>Trage deine E-Mail-Adresse ein. Falls sie auf der Newsletter steht, senden wir dir einen geschützten Abmeldelink.</p><form method="post" action="/newsletter.php"><input type="hidden" name="action" value="unsubscribe-request"><label>E-Mail-Adresse <input type="email" name="email" autocomplete="email" maxlength="254" required style="display:block;box-sizing:border-box;width:100%;padding:12px;margin:10px 0 18px"></label><button type="submit">Abmeldelink anfordern</button></form>');
}
if($action==='unsubscribe-request' && $method==='POST'){
 $email=strtolower(trim((string)($_POST['email']??'')));
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254)page('Abmeldung','<p>Falls deine Adresse registriert ist, erhältst du eine E-Mail mit dem Abmeldelink.</p>');
 $ip=hashed($cfg['key'].'|'.($_SERVER['REMOTE_ADDR']??'unknown'));
 $q=$db->prepare('SELECT COUNT(*) FROM attempts WHERE ip_hash=? AND created_at>?');$q->execute([$ip,$now-3600]);
 if((int)$q->fetchColumn()>=6)page('Zu viele Anfragen','<p>Bitte versuche es später erneut oder schreibe an news@tennis-schiri.de.</p>',429);
 $db->prepare('INSERT INTO attempts(ip_hash,created_at) VALUES(?,?)')->execute([$ip,$now]);
 $q=$db->prepare('SELECT id FROM subscribers WHERE email=? AND confirmed_at IS NOT NULL');$q->execute([$email]);
 $r=$q->fetch();
 if($r){
  $t=token();
  $db->prepare('UPDATE subscribers SET unsubscribe_hash=?, expires_at=? WHERE id=?')->execute([hashed($t),$now+2*86400,$r['id']]);
  $link='https://tennis-schiri.de/newsletter.php?action=unsubscribe&token='.$t;
  $body="Hier kannst du deine Tennis-Schiri Newsletter-Anmeldung loeschen:\n\n".$link."\n\nDieser Link ist 48 Stunden gueltig. Falls du die Abmeldung nicht angefordert hast, ignoriere diese Nachricht.\nKontakt: news@tennis-schiri.de";
  if(!mailLink($cfg,$email,'Tennis-Schiri Newsletter: Abmeldung bestaetigen',$body))error_log('Tennis-Schiri Newsletter unsubscribe mail() failed');
 }
 page('E-Mail prüfen','<p>Falls deine Adresse registriert ist, erhältst du einen Abmeldelink per E-Mail. Prüfe gegebenenfalls auch den Spamordner.</p>');
}
if($action==='unsubscribe' && $method==='GET'){
 $t=(string)($_GET['token']??'');if(!preg_match('/^[a-f0-9]{64}$/',$t))page('Link ungültig','<p>Der Link ist ungültig.</p>',400);
 $q=$db->prepare('SELECT id FROM subscribers WHERE unsubscribe_hash=? AND confirmed_at IS NOT NULL AND expires_at>=?');$q->execute([hashed($t),$now]);
 if(!$q->fetch())page('Link ungültig','<p>Dieser Abmeldelink ist nicht gültig.</p>',400);
 page('Vom Newsletter abmelden','<form method="post" action="/newsletter.php"><input type="hidden" name="action" value="unsubscribe"><input type="hidden" name="token" value="'.htmlspecialchars($t,ENT_QUOTES).'"><p>Möchtest du deine Anmeldung löschen?</p><button type="submit">Jetzt abmelden</button></form>');
}
if($action==='unsubscribe' && $method==='POST'){
 $t=(string)($_POST['token']??'');if(!preg_match('/^[a-f0-9]{64}$/',$t))page('Link ungültig','<p>Der Link ist ungültig.</p>',400);
 $db->prepare('DELETE FROM subscribers WHERE unsubscribe_hash=? AND confirmed_at IS NOT NULL AND expires_at>=?')->execute([hashed($t),$now]);
 page('Abgemeldet','<p>Dein Newsletterneintrag wurde gelöscht.</p>');
}
page('Tennis-Schiri Newsletter','<p>Bitte nutze das Anmeldeformular auf der <a href="/newsletter/">Newsletter-Seite</a>.</p>',404);
