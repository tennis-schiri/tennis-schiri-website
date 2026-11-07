<?php
declare(strict_types=1);
define('TS_WORKSHOP',true);
require_once __DIR__.'/workshop-lib.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");

function wsEsc(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function wsPage(string $title,string $body,int $code=200): never {
 http_response_code($code);header('Content-Type: text/html; charset=UTF-8');
 echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>'.wsEsc($title).' | Tennis-Schiri</title><style>body{margin:0;background:#eef3e8;color:#173d2d;font:16px/1.65 system-ui,-apple-system,"Segoe UI",sans-serif}.wrap{max-width:690px;margin:7vh auto;padding:0 18px}.card{background:#fff;border:1px solid #dbe7d5;border-radius:20px;overflow:hidden;box-shadow:0 20px 55px #173d2d12}.head{background:#143b2c;padding:28px 32px;border-bottom:5px solid #c8f16d}.head img{display:block;width:250px;max-width:100%;height:auto}.body{padding:34px 32px}h1{font-size:34px;letter-spacing:-.04em;margin:0 0 17px}p{margin:0 0 16px}.summary{background:#f1f7e9;border:1px solid #d8e7c8;border-radius:12px;padding:18px 20px;margin:22px 0}.summary strong{color:#143b2c}a{color:#276744;font-weight:700}.btn{display:inline-block;background:#173d2d;color:#fff;text-decoration:none;padding:12px 18px;border-radius:9px;margin-top:6px}@media(max-width:600px){.body,.head{padding:24px 21px}h1{font-size:29px}}</style></head><body><main class="wrap"><section class="card"><div class="head"><a href="/"><img src="/assets/branding/ts-logo-dunkel.png?v=20261107-3" alt="Tennis-Schiri"></a></div><div class="body"><h1>'.wsEsc($title).'</h1>'.$body.'<a class="btn" href="/#regelworkshop-anmeldung">Zur Startseite</a></div></section></main></body></html>';exit;
}
function wsConfig(): array {
 $path=dirname(__DIR__).'/tennis-schiri-newsletter-config.php';
 if(!is_file($path))wsPage('Anmeldung derzeit nicht verfügbar','<p>Die Online-Anmeldung ist noch nicht freigeschaltet.</p>',503);
 $cfg=require $path;
 if(!is_array($cfg)||($cfg['enabled']??false)!==true||empty($cfg['database'])||empty($cfg['key']))wsPage('Anmeldung derzeit nicht verfügbar','<p>Die Online-Anmeldung ist noch nicht freigeschaltet.</p>',503);
 $root=realpath(__DIR__);$folder=realpath(dirname((string)$cfg['database']));
 if(!$root||!$folder||str_starts_with($folder.DIRECTORY_SEPARATOR,$root.DIRECTORY_SEPARATOR))wsPage('Konfigurationsfehler','<p>Der private Speicher ist nicht verfügbar.</p>',503);
 return $cfg;
}
function wsDb(array $cfg): PDO {
 try{$db=new PDO('sqlite:'.$cfg['database'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$db->exec('PRAGMA busy_timeout=5000');workshopSchema($db);return $db;}
 catch(Throwable $e){error_log('Workshop DB: '.$e->getMessage());wsPage('Vorübergehend nicht verfügbar','<p>Bitte versuche die Anmeldung später noch einmal.</p>',503);}
}

$method=$_SERVER['REQUEST_METHOD']??'GET';
$action=$method==='POST'?(string)($_POST['action']??''):(string)($_GET['action']??'');
if($action==='cancel'&&in_array($method,['GET','POST'],true)){
 $workshopId=(string)(($method==='POST'?$_POST:$_GET)['workshop']??'');
 $registrationId=filter_var(($method==='POST'?$_POST:$_GET)['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
 $token=(string)(($method==='POST'?$_POST:$_GET)['token']??'');
 $catalog=workshopCatalog();
 if(!isset($catalog[$workshopId])||!$registrationId||!preg_match('/^[a-f0-9]{64}$/D',$token))wsPage('Link ungültig','<p>Der Abmeldelink ist ungültig. Bitte kontaktiere turniere@tennis-schiri.de.</p>',404);
 $cfg=wsConfig();$db=wsDb($cfg);
 $q=$db->prepare('SELECT * FROM workshop_registrations WHERE id=? AND workshop_id=?');$q->execute([$registrationId,$workshopId]);$reg=$q->fetch();
 if(!$reg||!hash_equals(workshopCancelToken($reg,(string)$cfg['key']),$token))wsPage('Link ungültig','<p>Der Abmeldelink ist ungültig oder nicht mehr aktuell. Bitte kontaktiere turniere@tennis-schiri.de.</p>',404);
 if($reg['status']==='cancelled')wsPage('Bereits abgemeldet','<p>Deine Abmeldung wurde bereits gespeichert. Du musst nichts weiter tun.</p>');
 $ws=$catalog[$workshopId];
 if($method==='GET'){
  $form='<form method="post" action="/workshop.php"><input type="hidden" name="action" value="cancel"><input type="hidden" name="workshop" value="'.wsEsc($workshopId).'"><input type="hidden" name="id" value="'.$registrationId.'"><input type="hidden" name="token" value="'.wsEsc($token).'"><p><button type="submit" style="background:#953b31;color:#fff;border:0;border-radius:9px;padding:14px 18px;font:inherit;font-weight:750;cursor:pointer">Ja, verbindlich abmelden</button></p></form>';
  wsPage('Abmeldung bestätigen','<p>Möchtest du dich wirklich von diesem Workshop abmelden?</p><div class="summary"><strong>'.wsEsc($ws['title']).'</strong><br>'.wsEsc($ws['date']).' · '.wsEsc($ws['time']).'<br>'.wsEsc($ws['location']).'</div>'.$form.'<p>Falls du bereits bezahlt hast, klären wir eine Erstattung separat.</p>');
 }
 $origin=(string)($_SERVER['HTTP_ORIGIN']??'');
 if($origin!==''&&!in_array($origin,['https://tennis-schiri.de','https://www.tennis-schiri.de'],true))wsPage('Ungültige Anfrage','<p>Bitte rufe den Abmeldelink aus deiner E-Mail auf.</p>',403);
 $now=time();
 $q=$db->prepare("UPDATE workshop_registrations SET status='cancelled',updated_at=? WHERE id=? AND workshop_id=? AND status='registered' AND cancel_version=?");
 $q->execute([$now,$registrationId,$workshopId,(int)$reg['cancel_version']]);
 if($q->rowCount()!==1)wsPage('Bereits abgemeldet','<p>Die Abmeldung ist bereits verarbeitet.</p>');
 [$subject,$plain,$html]=workshopMailContent($ws,$reg,'cancelled');
 $sent=workshopSendMail((string)$reg['email'],$subject,$plain,$html);
 if($sent)$db->prepare("UPDATE workshop_registrations SET last_mail_type='cancelled',last_mail_at=? WHERE id=?")->execute([$now,$registrationId]);
 else error_log('Workshop cancellation mail not accepted for registration '.$registrationId);
 [$adminSubject,$adminText,$adminHtml]=workshopAdminCancellationNotification($ws,$reg);
 if(!workshopSendMail('mw@tennis-schiri.de',$adminSubject,$adminText,$adminHtml))error_log('Workshop cancellation admin mail not accepted.');
 wsPage('Abmeldung bestätigt','<p>Du bist vom Regelworkshop abgemeldet. Dein Platz wurde freigegeben.</p><div class="summary"><strong>'.wsEsc($ws['title']).'</strong><br>'.wsEsc($ws['date']).' · '.wsEsc($ws['time']).'</div><p>'.($sent?'Eine Bestätigung wurde per E-Mail versendet.':'Die Abmeldung wurde gespeichert; eine automatische E-Mail-Bestätigung konnte nicht versendet werden.').'</p><p>Bei bereits geleisteter Zahlung melde dich bitte zur Klärung unter <a href="mailto:turniere@tennis-schiri.de">turniere@tennis-schiri.de</a>.</p>');
}
if($method!=='POST'||$action!=='register')wsPage('Regelworkshop-Anmeldung','<p>Bitte nutze das Anmeldeformular auf der Tennis-Schiri-Website.</p>',404);
if(!empty($_POST['website']))wsPage('Vielen Dank','<p>Deine Anfrage wurde verarbeitet.</p>');
$origin=(string)($_SERVER['HTTP_ORIGIN']??'');
if($origin!==''&&!in_array($origin,['https://tennis-schiri.de','https://www.tennis-schiri.de'],true))wsPage('Ungültige Anfrage','<p>Bitte nutze das Formular auf tennis-schiri.de.</p>',403);

$catalog=workshopCatalog();$workshopId=substr(trim((string)($_POST['workshop']??'')),0,100);
if(!isset($catalog[$workshopId]))wsPage('Workshop nicht gefunden','<p>Der gewählte Workshop ist nicht verfügbar.</p>',404);
$workshop=$catalog[$workshopId];
$name=substr(trim((string)($_POST['name']??'')),0,160);
$email=strtolower(substr(trim((string)($_POST['email']??'')),0,254));
$club=substr(trim((string)($_POST['club']??'')),0,160);
$isMember=($_POST['member']??'')==='yes';
$payment=(string)($_POST['payment']??'onsite');if(!in_array($payment,['onsite','paypal'],true))$payment='onsite';
if($name===''||strlen($name)<2||!workshopSafeEmail($email)||($_POST['privacy']??'')!=='yes')wsPage('Angaben prüfen','<p>Bitte prüfe Name, E-Mail-Adresse und die Bestätigung der Datenschutzhinweise.</p>',422);

$cfg=wsConfig();$db=wsDb($cfg);$now=time();
$db->prepare('DELETE FROM workshop_attempts WHERE created_at < ?')->execute([$now-86400]);
$ipHash=hash('sha256',(string)$cfg['key'].'|workshop|'.($_SERVER['REMOTE_ADDR']??'unknown'));
$q=$db->prepare('SELECT COUNT(*) FROM workshop_attempts WHERE ip_hash=? AND created_at>?');$q->execute([$ipHash,$now-3600]);
if((int)$q->fetchColumn()>=8)wsPage('Zu viele Anfragen','<p>Bitte versuche es später erneut oder melde dich per E-Mail.</p>',429);
$db->prepare('INSERT INTO workshop_attempts(ip_hash,created_at) VALUES(?,?)')->execute([$ipHash,$now]);

$fee=$isMember?0:(int)$workshop['guest_fee_cents'];$paymentMethod=$isMember?'free':$payment;$paymentStatus=$isMember?'waived':'open';
if($isMember&&$club==='')$club=(string)$workshop['host_club'];
try{
 $q=$db->prepare("INSERT INTO workshop_registrations(workshop_id,name,email,club,is_member,fee_cents,payment_method,payment_status,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?, 'registered',?,?) ON CONFLICT(workshop_id,email) DO UPDATE SET name=excluded.name,club=excluded.club,is_member=excluded.is_member,fee_cents=excluded.fee_cents,payment_method=excluded.payment_method,payment_status=excluded.payment_status,status='registered',cancel_version=workshop_registrations.cancel_version+1,updated_at=excluded.updated_at");
 $q->execute([$workshopId,$name,$email,$club,$isMember?1:0,$fee,$paymentMethod,$paymentStatus,$now,$now]);
 $q=$db->prepare('SELECT * FROM workshop_registrations WHERE workshop_id=? AND email=?');$q->execute([$workshopId,$email]);$registration=$q->fetch();
 if(!$registration)throw new RuntimeException('Anmeldung konnte nicht gelesen werden.');
}catch(Throwable $e){error_log('Workshop registration: '.$e->getMessage());wsPage('Anmeldung konnte nicht gespeichert werden','<p>Bitte versuche es später erneut.</p>',503);}

$registration['cancel_url']=workshopCancelUrl($registration,(string)$cfg['key']);
$paypalInfo=workshopSetting($db,'paypal_instructions');
[$subject,$text,$html]=workshopMailContent($workshop,$registration,'confirmation',$paypalInfo);
$confirmationSent=workshopSendMail($email,$subject,$text,$html);
if($confirmationSent)$db->prepare("UPDATE workshop_registrations SET confirmation_sent_at=?,last_mail_type='confirmation',last_mail_at=?,updated_at=? WHERE id=?")->execute([$now,$now,$now,$registration['id']]);
else error_log('Workshop confirmation mail not accepted: '.$email);
[$adminSubject,$adminText,$adminHtml]=workshopAdminNotification($workshop,$registration);
if(!workshopSendMail('mw@tennis-schiri.de',$adminSubject,$adminText,$adminHtml))error_log('Workshop admin notification not accepted.');

$payText=$isMember?'Für Mitglieder des NTC 93 ist die Teilnahme kostenfrei.':('Teilnahmebeitrag: '.workshopFeeLabel($fee).' · '.($paymentMethod==='paypal'?'Der PayPal-Zahlungslink steht in deiner Bestätigungsmail.':'zahlbar vor Ort.'));
$mailNote=$confirmationSent?'Eine Bestätigung wurde an '.wsEsc($email).' geschickt.':'Deine Anmeldung ist gespeichert. Die automatische Bestätigungsmail konnte technisch nicht versendet werden.';
wsPage('Anmeldung eingegangen','<p>Dein Platz für den Regelworkshop ist vorgemerkt.</p><div class="summary"><strong>'.wsEsc($workshop['title']).'</strong><br>'.wsEsc($workshop['date']).' · '.wsEsc($workshop['time']).'<br>'.wsEsc($workshop['location']).'<br>'.wsEsc($workshop['address']).'<br><br>'.wsEsc($payText).'</div><p>'.$mailNote.'</p><p>Falls du doch nicht teilnehmen kannst, nutze den Abmeldelink in deiner Bestätigungsmail oder schreibe an <a href="mailto:turniere@tennis-schiri.de">turniere@tennis-schiri.de</a>.</p>');
