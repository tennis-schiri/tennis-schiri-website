<?php
declare(strict_types=1);
/* Turnier-Info-Mails. Administrative mail only; independent of newsletter consent. */
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
$private=dirname(__DIR__).'/tennis-schiri-newsletter-config.php';
if (!is_file($private)) { http_response_code(503); exit('Verwaltung nicht eingerichtet.'); }
$cfg=require $private;
if (!is_array($cfg)||($cfg['enabled']??false)!==true||empty($cfg['database'])||empty($cfg['key'])) { http_response_code(503); exit('Verwaltung nicht freigeschaltet.'); }
session_name('ts_newsletter_admin');
session_set_cookie_params(['httponly'=>true,'secure'=>true,'samesite'=>'Strict','path'=>'/']);
session_start();
if(empty($_SESSION['authenticated'])||(int)($_SESSION['auth_at']??0)<time()-8*3600) { header('Location: /newsletter-admin.php',true,303); exit; }
if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24));
function esc(mixed $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function validEmail(string $v): bool { return strlen($v)<=254 && filter_var($v,FILTER_VALIDATE_EMAIL)!==false && !preg_match('/[\r\n]/',$v); }
function urlValid(string $url): bool { if($url==='')return true; $p=parse_url($url);return strlen($url)<=500&&filter_var($url,FILTER_VALIDATE_URL)&&is_array($p)&&strtolower((string)($p['scheme']??''))==='https'&&!empty($p['host'])&&!isset($p['user'])&&!isset($p['pass']); }
function redirectInfo(string $msg,int $id=0): never { header('Location: /turnier-mails.php?'.($id?'id='.$id.'&':'').'info='.rawurlencode($msg),true,303);exit; }
function csrfInput(): string { return '<input type="hidden" name="csrf" value="'.esc($_SESSION['csrf']).'">'; }
function renderPage(string $html): never {
 echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Turnier-Mails · Tennis-Schiri</title><style>*{box-sizing:border-box}body{font:15px/1.6 system-ui;background:#eef3e8;color:#173c2c;margin:0}main{max-width:1000px;margin:30px auto;padding:0 14px}.panel{padding:23px;background:white;border:1px solid #d8e5d7;border-radius:14px;margin:16px 0}a{color:#236b44}h1{font-size:30px}h2{font-size:20px}label{display:block;font-weight:700;margin:12px 0 4px}input,textarea{width:100%;font:inherit;padding:10px;border-radius:7px;border:1px solid #adc5b3}textarea{min-height:130px}button{background:#143b2c;color:white;border:0;border-radius:8px;padding:11px 16px;cursor:pointer;margin:12px 6px 4px 0;font:inherit}table{width:100%;border-collapse:collapse}td,th{padding:9px;border-bottom:1px solid #e5ede5;text-align:left}small{color:#55705d}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}@media(max-width:650px){.grid{grid-template-columns:1fr}table{font-size:12px}}.notice{padding:13px;border-radius:8px;background:#eff7dd}.warn{background:#fff3e8;padding:13px;border-radius:8px}</style><main><img src="/assets/branding/ts-logo-hell.png?v=20261107-3" width="245" alt="Tennis-Schiri" style="max-width:100%;height:auto"><p><a href="/newsletter-admin.php">← Verwaltung</a> · <a href="/newsletter-mail.php">Newsletter-Mails</a></p><h1>Turnier-Mails</h1><p>Organisatorische Teilnehmerinformationen, getrennt vom Newsletter. Absender und Antwortadresse: <strong>turniere@tennis-schiri.de</strong>.</p>'.$html.'</main></html>';exit;
}
try {
 $dbPath=dirname((string)$cfg['database']).'/tennis-schiri-turnier-mails.sqlite';
 $db=new PDO('sqlite:'.$dbPath,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $db->exec('PRAGMA busy_timeout=5000');
 $db->exec("CREATE TABLE IF NOT EXISTS tournaments (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,day TEXT NOT NULL,venue TEXT NOT NULL,url TEXT NOT NULL DEFAULT '',subject TEXT NOT NULL DEFAULT '',body TEXT NOT NULL DEFAULT '',state TEXT NOT NULL DEFAULT 'draft',created_at INTEGER NOT NULL)");
 $db->exec("CREATE TABLE IF NOT EXISTS recipients (tournament_id INTEGER NOT NULL,email TEXT NOT NULL,added_at INTEGER NOT NULL,PRIMARY KEY(tournament_id,email))");
 $db->exec("CREATE TABLE IF NOT EXISTS deliveries (tournament_id INTEGER NOT NULL,email TEXT NOT NULL,state TEXT NOT NULL DEFAULT 'pending',attempted_at INTEGER,PRIMARY KEY(tournament_id,email))");
} catch(Throwable $ex) { error_log('Turnier-Mails database: '.$ex->getMessage());http_response_code(503);exit('Privater Datenspeicher nicht erreichbar.'); }
function tournament(PDO $db,int $id): ?array { $s=$db->prepare('SELECT * FROM tournaments WHERE id=?');$s->execute([$id]);return $s->fetch()?:null; }
function bodyText(array $t): string {
 return "Hallo zusammen,\n\nwir freuen uns, euch beim Turnier ". $t['name']." am ".$t['day']." in ".$t['venue']." begrüßen zu dürfen.\n\n".$t['body']."\n\n".($t['url']!==''?"Turnierdetails: ".$t['url']."\n\n":'')."Sportliche Grüße\nMichael\nTennis-Schiri\n\nRückfragen: turniere@tennis-schiri.de";
}
function mailHtml(array $t,bool $preview): string {
 $parts=preg_split('/\R{2,}/u',trim($t['body']))?:[];
 $p='';
 foreach($parts as $part){$p.='<p style="font:16px/1.75 Arial,sans-serif;color:#3d5649;margin:0 0 19px">'.nl2br(esc($part),false).'</p>';}
 $link=$t['url']!==''?'<p><a style="display:inline-block;background:#c2ee7b;color:#143b2c;padding:15px 23px;border-radius:7px;text-decoration:none;font-weight:bold" href="'.esc($t['url']).'">Zur Turnierseite</a></p>':'';
 $banner=$preview?'<tr><td style="background:#ffefc5;padding:12px;text-align:center;font-weight:bold">TEST / VORSCHAU – kein Teilnehmer-Versand</td></tr>':'';
 return '<!doctype html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;background:#eef3e8;font-family:Arial,Helvetica,sans-serif"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef3e8;padding:24px 8px"><tr><td align="center"><table role="presentation" cellspacing="0" cellpadding="0" width="600" style="width:100%;max-width:600px;background:white"><tr><td style="background:#143b2c;padding:29px 32px"><img src="https://tennis-schiri.de/assets/branding/ts-logo-dunkel.png?v=20261107-3" width="245" alt="Tennis-Schiri" style="display:block;max-width:100%;height:auto"><p style="color:#c2ee7b;letter-spacing:2px;font-size:11px;margin:16px 0 0">TURNIER-INFORMATIONEN</p></td></tr><tr><td bgcolor="#c2ee7b" height="5"></td></tr>'.$banner.'<tr><td style="padding:32px"><h1 style="font-size:30px;line-height:1.15;color:#173c2c">'.esc($t['name']).'</h1><table width="100%" role="presentation" style="background:#f2f7ed;margin:0 0 24px"><tr><td style="padding:18px"><strong>'.esc($t['day']).'</strong><p style="margin:7px 0 0">'.esc($t['venue']).'</p></td></tr></table><p style="font:16px/1.7 Arial,sans-serif">Hallo zusammen,<br>wir freuen uns, euch beim Turnier begrüßen zu dürfen. Bitte beachtet diese Informationen:</p>'.$p.$link.'<p>Sportliche Grüße<br><strong>Michael</strong></p></td></tr><tr><td style="background:#f2f7ed;padding:23px 32px;font-size:13px;color:#45634c">Tennis-Schiri · <a href="https://tennis-schiri.de/">tennis-schiri.de</a><br>Rückfragen: <a href="mailto:turniere@tennis-schiri.de">turniere@tennis-schiri.de</a><br>Diese Nachricht betrifft deine Teilnahme am oben genannten Turnier.</td></tr></table></td></tr></table></body></html>';
}
function sendOne(string $recipient,array $t,bool $test): bool {
 if(!validEmail($recipient))return false;
 $boundary='tst_'.bin2hex(random_bytes(12));
 $subject=$test?'[TEST] '.$t['subject']:$t['subject'];
 $headers=['From: Tennis-Schiri Turniere <turniere@tennis-schiri.de>','Reply-To: turniere@tennis-schiri.de','MIME-Version: 1.0','Content-Type: multipart/alternative; boundary="'.$boundary.'"','X-Auto-Response-Suppress: All'];
 $message="This is a multipart MIME message.\r\n";
 foreach([['text/plain',bodyText($t)],['text/html',mailHtml($t,$test)]] as [$type,$value]) {
  $message.="--".$boundary."\r\nContent-Type: ".$type."; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($value),76,"\r\n");
 }
 $message.="--".$boundary."--\r\n";
 return mail($recipient,'=?UTF-8?B?'.base64_encode($subject).'?=',$message,implode("\r\n",$headers),'-fturniere@tennis-schiri.de');
}
$id=filter_var($_POST['id']??$_GET['id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!hash_equals((string)$_SESSION['csrf'],(string)($_POST['csrf']??''))) {http_response_code(403);renderPage('<div class="warn">Ungültige Sitzung.</div>');}
 $action=(string)($_POST['action']??'');
 if($action==='create'){
  $name=trim((string)($_POST['name']??''));$day=trim((string)($_POST['day']??''));$venue=trim((string)($_POST['venue']??''));$url=trim((string)($_POST['url']??''));
  if($name===''||$day===''||$venue===''||strlen($name)>140||strlen($day)>80||strlen($venue)>160||!urlValid($url))renderPage('<div class="warn">Turnierdaten prüfen.</div>');
  $s=$db->prepare('INSERT INTO tournaments(name,day,venue,url,subject,body,created_at) VALUES(?,?,?,?,?,?,?)');
  $s->execute([$name,$day,$venue,$url,'Wichtige Informationen: '.$name,"Bitte seid 30 Minuten vor eurer angesetzten Spielzeit vor Ort.\n\nDie Einspielzeit beträgt fünf Minuten. Ergebnisse bitte direkt nach dem Match bei der Turnierleitung melden.\n\nNenngeld und Hinweise zur Anreise: [BITTE ERGÄNZEN]",time()]);
  redirectInfo('Turnier angelegt. Bitte Inhalte prüfen.',(int)$db->lastInsertId());
 }
 $t=tournament($db,$id);
 if(!$t)renderPage('<div class="warn">Turnier nicht gefunden.</div>');
 if($action==='save'){
  if($t['state']!=='draft')renderPage('<div class="warn">Nach Freigabe nicht mehr veränderbar. Für Änderungen ein neues Turnier beziehungsweise einen neuen Versand anlegen.</div>');
  $subject=trim((string)($_POST['subject']??''));$body=trim((string)($_POST['body']??''));
  if($subject===''||strlen($subject)>160||preg_match('/[\r\n]/',$subject)||$body===''||strlen($body)>12000)renderPage('<div class="warn">Betreff und Text prüfen.</div>');
  $s=$db->prepare('UPDATE tournaments SET subject=?,body=? WHERE id=? AND state=?');$s->execute([$subject,$body,$id,'draft']);
  redirectInfo('Entwurf gespeichert. Vor dem Versand testen.',$id);
 }
 if($action==='import'){
  if($t['state']!=='draft')renderPage('<div class="warn">Teilnehmerliste bereits eingefroren.</div>');
  $raw=trim((string)($_POST['emails']??''));
  if(!empty($_FILES['csv']['tmp_name'])&&is_uploaded_file($_FILES['csv']['tmp_name'])){
   if((int)($_FILES['csv']['size']??0)>500000)renderPage('<div class="warn">CSV zu groß (max. 500 KB).</div>');
   $raw=(string)file_get_contents($_FILES['csv']['tmp_name']);
  }
  if(strlen($raw)>500000)renderPage('<div class="warn">Import zu groß.</div>');
  $found=[];
  foreach(preg_split('/\R/u',$raw)?:[] as $line){
   foreach(preg_split('/[;,\t]/u',$line)?:[] as $cell){
    $v=strtolower(trim($cell," \t\n\r\0\x0B\"'"));
    if(validEmail($v))$found[$v]=true;
   }
  }
  if(!$found||count($found)>2000)renderPage('<div class="warn">Keine gültigen Adressen gefunden oder Limit von 2.000 überschritten. CSV bitte mit einer E-Mail-Spalte importieren.</div>');
  $db->beginTransaction();
  try {
   $s=$db->prepare('INSERT OR IGNORE INTO recipients(tournament_id,email,added_at) VALUES(?,?,?)');
   foreach(array_keys($found) as $mail)$s->execute([$id,$mail,time()]);
   $db->commit();
  }catch(Throwable $e){$db->rollBack();throw $e;}
  redirectInfo(count($found).' eindeutige gültige Adressen verarbeitet.',$id);
 }
 if($action==='test'){
  $email=trim((string)($_POST['test_email']??''));
  if(!validEmail($email))renderPage('<div class="warn">Ungültige Testadresse.</div>');
  redirectInfo(sendOne($email,$t,true)?'Testmail vom Server angenommen. Eingang prüfen.':'Testversand fehlgeschlagen. Mailkonfiguration prüfen.',$id);
 }
 if($action==='approve'){
  $count=(int)$db->query('SELECT COUNT(*) FROM recipients WHERE tournament_id='.(int)$id)->fetchColumn();
  if($t['state']!=='draft'||$count===0||($_POST['confirm']??'')!=='FREIGEBEN')renderPage('<div class="warn">Nur Entwürfe mit Empfängern nach Eingabe von FREIGEBEN freigeben.</div>');
  $db->beginTransaction();
  try{
   $db->prepare("UPDATE tournaments SET state='approved' WHERE id=? AND state='draft'")->execute([$id]);
   $db->prepare("INSERT OR IGNORE INTO deliveries(tournament_id,email,state) SELECT tournament_id,email,'pending' FROM recipients WHERE tournament_id=?")->execute([$id]);
   $db->commit();
  }catch(Throwable $e){$db->rollBack();throw $e;}
  redirectInfo('Freigegeben. Noch keine Teilnehmer-Mail verschickt.',$id);
 }
 if($action==='send'){
  if($t['state']!=='approved'||($_POST['confirm']??'')!=='SENDEN')renderPage('<div class="warn">Versand nicht freigegeben.</div>');
  $rows=$db->prepare("SELECT email FROM deliveries WHERE tournament_id=? AND state='pending' ORDER BY email LIMIT 5");
  $rows->execute([$id]);$emails=$rows->fetchAll(PDO::FETCH_COLUMN);
  $ok=0;$failed=0;
  foreach($emails as $email){
   // Mark before calling mail(): uncertain delivery must never trigger automatic duplicate sends.
   $s=$db->prepare("UPDATE deliveries SET state='attempted',attempted_at=? WHERE tournament_id=? AND email=? AND state='pending'");
   $s->execute([time(),$id,$email]);
   if(!$s->rowCount())continue;
   $accepted=sendOne((string)$email,$t,false);
   $db->prepare("UPDATE deliveries SET state=? WHERE tournament_id=? AND email=? AND state='attempted'")->execute([$accepted?'accepted':'failed',$id,$email]);
   $accepted?$ok++:$failed++;
  }
  redirectInfo($ok.' an Mailserver übergeben, '.$failed.' fehlgeschlagen. Keine automatische Wiederholung.',$id);
 }
}
$all=$db->query('SELECT id,name,day,state FROM tournaments ORDER BY id DESC')->fetchAll();
$nav='<section class="panel"><h2>Turniere</h2><table><tr><th>Turnier</th><th>Datum</th><th>Status</th></tr>';
foreach($all as $row)$nav.='<tr><td><a href="?id='.(int)$row['id'].'">'.esc($row['name']).'</a></td><td>'.esc($row['day']).'</td><td>'.esc($row['state']).'</td></tr>';
$nav.='</table></section>';
$message=isset($_GET['info'])?'<p class="notice">'.esc(substr((string)$_GET['info'],0,300)).'</p>':'';
if($id){
 $t=tournament($db,$id);
 if(!$t)renderPage('<section class="panel">Turnier nicht gefunden.</section>'.$nav);
 $counts=$db->prepare('SELECT state,COUNT(*) c FROM deliveries WHERE tournament_id=? GROUP BY state');$counts->execute([$id]);
 $status=[];foreach($counts as $row)$status[$row['state']]=$row['c'];
 $q=$db->prepare('SELECT COUNT(*) FROM recipients WHERE tournament_id=?');$q->execute([$id]);$num=(int)$q->fetchColumn();
 $info='<section class="panel"><h2>'.esc($t['name']).'</h2><p>'.esc($t['day']).' · '.esc($t['venue']).' · Status: <strong>'.esc($t['state']).'</strong></p><p>'.$num.' Empfänger · '.esc(json_encode($status,JSON_UNESCAPED_UNICODE)).'</p><p><a href="?id='.$id.'&preview=1" target="_blank" rel="noopener">HTML-Vorschau öffnen</a></p></section>';
 if(isset($_GET['preview'])){header('Content-Type: text/html; charset=UTF-8');echo mailHtml($t,true);exit;}
 if($t['state']==='draft'){
  $info.='<section class="panel"><h2>Nachricht bearbeiten</h2><form method="post">'.csrfInput().'<input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="action" value="save"><label>Betreff</label><input name="subject" maxlength="160" required value="'.esc($t['subject']).'"><label>Text (Absätze mit Leerzeile trennen)</label><textarea name="body" rows="13" required>'.esc($t['body']).'</textarea><button>Entwurf speichern</button></form></section>';
  $info.='<section class="panel"><h2>Teilnehmer importieren</h2><p>Eine E-Mail-Adresse pro Zeile oder CSV mit E-Mail-Spalte. Keine Newsletter-Abonnenten importieren. Nur Teilnehmer dieses Turniers.</p><form method="post" enctype="multipart/form-data">'.csrfInput().'<input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="action" value="import"><label>CSV hochladen (max. 500 KB)</label><input type="file" name="csv" accept=".csv,text/csv"><label>Oder E-Mail-Adressen einfügen</label><textarea name="emails" placeholder="spieler@example.de"></textarea><button>Adressen importieren</button></form></section>';
 }
 $info.='<section class="panel"><h2>Testversand</h2><form method="post">'.csrfInput().'<input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="action" value="test"><label>Testadresse</label><input type="email" name="test_email" value="mw@tennis-schiri.de" required><button>Testmail senden</button></form></section>';
 if($t['state']==='draft')$info.='<section class="panel"><h2>Empfängerkreis freigeben</h2><p>Vorher Vorschau, Empfängerliste und Testmail kontrollieren. Nach Freigabe sind Text und Empfängerliste gesperrt.</p><form method="post">'.csrfInput().'<input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="action" value="approve"><label>FREIGEBEN eingeben</label><input name="confirm" required><button>Empfänger freigeben</button></form></section>';
 if($t['state']==='approved')$info.='<section class="panel"><h2>Versand</h2><p>Manuell in Paketen zu maximal 5 E-Mails. Ohne automatische Wiederholung. Angenommen bedeutet nicht zugestellt.</p><form method="post">'.csrfInput().'<input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="action" value="send"><label>SENDEN eingeben</label><input name="confirm" required><button>Nächste 5 Nachrichten versenden</button></form></section>';
 renderPage($message.$info.$nav);
}
renderPage($message.'<section class="panel"><h2>Neues Turnier</h2><form method="post">'.csrfInput().'<input type="hidden" name="action" value="create"><label>Turniername</label><input name="name" maxlength="140" required><div class="grid"><div><label>Termin</label><input name="day" maxlength="80" placeholder="Samstag, 21.11.2026" required></div><div><label>Veranstaltungsort</label><input name="venue" maxlength="160" required></div></div><label>Turnierseite (HTTPS, optional)</label><input type="url" name="url" placeholder="https://tennis-schiri.de/turniere/"><button>Turnier anlegen</button></form></section>'.$nav);
