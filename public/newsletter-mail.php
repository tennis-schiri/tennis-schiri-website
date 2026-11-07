<?php
declare(strict_types=1);
/* Tennis-Schiri Newsletter: opt-in-only newsletter management. All sends require an admin POST. */
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
$private = dirname(__DIR__).'/tennis-schiri-newsletter-config.php';
if (!is_file($private)) { http_response_code(503); exit('Konfiguration nicht verfügbar.'); }
$cfg = require $private;
if (!is_array($cfg) || ($cfg['enabled']??false)!==true || empty($cfg['database']) || empty($cfg['key'])) {
 http_response_code(503); exit('Newsletter-Verwaltung nicht verfügbar.');
}
session_name('ts_newsletter_admin');
session_set_cookie_params(['httponly'=>true,'secure'=>true,'samesite'=>'Strict','path'=>'/']);
session_start();
if (empty($_SESSION['authenticated']) || (int)($_SESSION['auth_at']??0)<time()-8*3600) {
 $_SESSION=[];
 header('Location: /newsletter-admin.php',true,303); exit;
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24));
try {
 $db=new PDO('sqlite:'.$cfg['database'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $db->exec('PRAGMA busy_timeout=5000');
 $db->exec("CREATE TABLE IF NOT EXISTS newsletter_campaigns (
  id INTEGER PRIMARY KEY AUTOINCREMENT, subject TEXT NOT NULL, body TEXT NOT NULL,
  topic TEXT NOT NULL, state TEXT NOT NULL DEFAULT 'draft', created_at INTEGER NOT NULL, updated_at INTEGER NOT NULL)");
 $db->exec("CREATE TABLE IF NOT EXISTS newsletter_deliveries (
  campaign_id INTEGER NOT NULL, subscriber_id INTEGER NOT NULL, recipient_key TEXT NOT NULL,
  state TEXT NOT NULL DEFAULT 'pending', attempted_at INTEGER,
  PRIMARY KEY (campaign_id,subscriber_id))");
 $columns=array_column($db->query('PRAGMA table_info(newsletter_campaigns)')->fetchAll(),'name');
 foreach (['headline'=>'TEXT NOT NULL DEFAULT \'\'','preheader'=>'TEXT NOT NULL DEFAULT \'\'',
           'button_label'=>'TEXT NOT NULL DEFAULT \'\'','button_url'=>'TEXT NOT NULL DEFAULT \'\'',
           'event_title'=>'TEXT NOT NULL DEFAULT \'\'','event_date'=>'TEXT NOT NULL DEFAULT \'\'',
           'event_location'=>'TEXT NOT NULL DEFAULT \'\'', 'selected_events'=>"TEXT NOT NULL DEFAULT '[]'",
           'selected_partners'=>"TEXT NOT NULL DEFAULT '[]'"] as $column=>$definition) {
  if (!in_array($column,$columns,true)) $db->exec('ALTER TABLE newsletter_campaigns ADD COLUMN '.$column.' '.$definition);
 }
} catch(Throwable $e) { error_log('Newsletter campaigns setup failed: '.$e->getMessage()); http_response_code(503); exit('Datenbank vorübergehend nicht verfügbar.'); }
function h(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
define('TS_NEWSLETTER_TEMPLATE',true);
require_once __DIR__.'/newsletter-template.php';
define('TS_NEWSLETTER_EVENTS',true);
require_once __DIR__.'/newsletter-events.php';
define('TS_NEWSLETTER_PARTNERS',true);
require_once __DIR__.'/newsletter-partners.php';
function validButtonUrl(string $url): bool {
 if ($url==='') return true;
 if (strlen($url)>500 || preg_match('/[\r\n]/',$url) || !filter_var($url,FILTER_VALIDATE_URL)) return false;
 $parts=parse_url($url);
 return is_array($parts) && strtolower((string)($parts['scheme']??''))==='https'
    && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']);
}
function extraFields(array $campaign=[]): string {
 $out='<label>Überschrift im Newsletter (optional)</label><input type="text" name="headline" maxlength="120" value="'.h($campaign['headline']??'').'" placeholder="Wenn leer, wird der Betreff verwendet">';
 $out.='<label>Vorschautext im Posteingang (optional)</label><input type="text" name="preheader" maxlength="160" value="'.h($campaign['preheader']??'').'" placeholder="Kurz und verständlich: Darum geht es in dieser Ausgabe">';
 $out.='<label>Button-Beschriftung (optional)</label><input type="text" name="button_label" maxlength="70" value="'.h($campaign['button_label']??'').'" placeholder="Zum Turnier anmelden">';
 $out.='<label>Button-Ziel (HTTPS-Link)</label><input type="url" name="button_url" maxlength="500" value="'.h($campaign['button_url']??'').'" placeholder="https://tennis-schiri.de/turniere/">';
 $out.='<p class="muted">Beide Button-Felder entweder ausfüllen oder leer lassen. Nur HTTPS-Links. Der Nachrichtentext wird sicher als Absätze formatiert, ohne HTML-Code eingeben zu müssen.</p>';
 $out.='<div class="event-editor"><strong>Optional: Veranstaltungskarte</strong><p class="muted">Für ein besonders wichtiges Turnier oder einen Workshop. Ohne Titel wird keine Karte angezeigt.</p>';
 $out.='<label>Turnier-/Workshop-Name</label><input type="text" name="event_title" maxlength="120" value="'.h($campaign['event_title']??'').'" placeholder="z. B. Hallen-LK-Turnier in Bad Saarow">';
 $out.='<label>Termin</label><input type="text" name="event_date" maxlength="80" value="'.h($campaign['event_date']??'').'" placeholder="z. B. 21. November 2026 · ab 10:00 Uhr">';
 $out.='<label>Veranstaltungsort</label><input type="text" name="event_location" maxlength="140" value="'.h($campaign['event_location']??'').'" placeholder="z. B. Bad Saarow · Sandplatzhalle"></div>';
 $out.=newsletterEventSelector((string)($campaign['selected_events']??'[]'));
 $out.=newsletterPartnersSelector((string)($campaign['selected_partners']??'[]'));
 return $out;
}

function finish(string $message,int $id=0): never {
 header('Location: /newsletter-mail.php?'.($id>0?'campaign='.$id.'&':'').'info='.rawurlencode($message),true,303); exit;
}
function page(string $contents): never {
 echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Tennis-Schiri Newsletter · E-Mail-Verwaltung</title><style>
 :root{color-scheme:light}*{box-sizing:border-box}body{margin:0;background:#eef3ef;color:#092b36;font:15px/1.55 system-ui}main{max-width:1050px;margin:36px auto;padding:0 17px}.panel{background:white;border:1px solid #d9e5df;border-radius:14px;padding:24px;margin-bottom:17px}h1{font-size:29px}h2{font-size:21px;margin:0 0 15px}p{margin:8px 0 16px}a{color:#007b7c}label{display:block;font-weight:700;margin:13px 0 6px}input[type=text],input[type=url],textarea,select{font:inherit;border:1px solid #b5c9c3;border-radius:8px;padding:11px;width:100%;background:white;color:#092b36}textarea{min-height:180px}button{background:#092b36;color:#fff;border:0;border-radius:8px;padding:11px 16px;font:inherit;cursor:pointer;margin:8px 8px 8px 0}button:hover{background:#19505b}.muted,small{color:#526e70}.event-editor{margin:23px 0;padding:20px;border:1px solid #c6dbbb;background:#f4f8ee;border-radius:12px}.event-editor>strong{display:block;color:#245039;font-size:17px}.event-editor .muted{margin:6px 0 18px}.newsletter-event-options{margin:28px 0;padding:22px;border:1px solid #c7dec8;border-radius:14px;background:#f2f8ee}.newsletter-event-options legend{font-weight:900;color:#244e36;padding:0 10px;font-size:17px}.newsletter-event-options h3{font-size:14px;margin:20px 0 10px;color:#326043}.newsletter-event-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.newsletter-event-option{background:#fff;border:1px solid #d6e5d4;border-radius:10px;padding:13px;display:flex;flex-direction:column;justify-content:space-between;gap:8px}.event-check{display:flex;gap:11px;align-items:start;cursor:pointer;margin:0}.event-check input{margin-top:4px;width:18px;height:18px;accent-color:#387c4c}.event-check strong{font-size:13px;color:#20472f;display:block}.event-check small{display:block;font-size:11px;line-height:1.55;margin-top:4px}.newsletter-event-option>a{font-weight:700;font-size:11px;color:#326e43;padding-left:30px}.event-old{color:#9b6b35;font-size:11px;font-weight:750}.newsletter-partner-option .partner-choice{display:flex;align-items:center;gap:12px}.newsletter-partner-option .partner-choice img{display:block;max-width:95px;width:95px;height:48px;object-fit:contain;flex-shrink:0;background:#fff;border-radius:6px}.newsletter-partner-option .partner-choice strong{display:block;font-size:13px;line-height:1.3;color:#214835}.newsletter-partner-option .partner-choice small{font-size:11px;color:#6d8174}.newsletter-partner-option>.event-check{gap:9px}@media(max-width:720px){.newsletter-event-grid{grid-template-columns:1fr}}.warning{border-left:4px solid #eb8959;padding:10px 15px;background:#fff8f2}.success{background:#e8f5e7;padding:12px;border-radius:8px}.row{display:flex;gap:16px;flex-wrap:wrap;align-items:center}.row>*{flex:1}.topic{font-weight:700}table{border-collapse:collapse;width:100%}th,td{border-bottom:1px solid #d9e5df;padding:10px;text-align:left}code{font-size:12px} .danger{color:#9c482b} @media(max-width:640px){.panel{padding:17px}table{font-size:12px}}
 </style><main><p><a href="/newsletter-admin.php">← Zur Newsletter-Verwaltung</a></p><h1>Newsletter · E-Mail-Verwaltung</h1>'.$contents.'</main></html>'; exit;
}
function csrf(): string { return '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">'; }
function safeEmail(string $email): bool { return (bool)filter_var($email,FILTER_VALIDATE_EMAIL) && strlen($email)<=254 && !str_contains($email,"\r") && !str_contains($email,"\n"); }
function subjectHeader(string $subject): string {
 if (function_exists('mb_encode_mimeheader')) return mb_encode_mimeheader($subject,'UTF-8','B',"\r\n");
 return '=?UTF-8?B?'.base64_encode($subject).'?=';
}
function sendMail(string $to,string $subject,string $text,string $html,?string $unsubscribeUrl=null): bool {
 if (!safeEmail($to) || preg_match('/[\r\n]/',$subject) || strlen($subject)>180) return false;
 $boundary='tsn_'.bin2hex(random_bytes(16));
 $headers=[
  'From: Tennis-Schiri Newsletter <news@tennis-schiri.de>',
  'Reply-To: news@tennis-schiri.de',
  'MIME-Version: 1.0',
  'Content-Type: multipart/alternative; boundary="'.$boundary.'"',
  'X-Auto-Response-Suppress: All'
 ];
 if ($unsubscribeUrl!==null) $headers[]='List-Unsubscribe: <'.$unsubscribeUrl.'>';
 $message="This is a multipart message in MIME format.\r\n";
 foreach ([['text/plain',$text],['text/html',$html]] as [$mime,$value]) {
  $message.="--".$boundary."\r\nContent-Type: ".$mime."; charset=UTF-8\r\n";
  $message.="Content-Transfer-Encoding: base64\r\n\r\n";
  $message.=chunk_split(base64_encode($value),76,"\r\n");
 }
 $message.="--".$boundary."--\r\n";
 return mail($to,subjectHeader($subject),$message,implode("\r\n",$headers),'-fnews@tennis-schiri.de');
}
function opted(string $encoded,string $topic): bool {
 $topics=json_decode($encoded,true);
 return is_array($topics) && in_array($topic,$topics,true);
}
function recipients(PDO $db,string $topic): array {
 $rows=$db->query('SELECT id,email,interests FROM subscribers WHERE confirmed_at IS NOT NULL ORDER BY id')->fetchAll();
 return array_values(array_filter($rows,static fn($r)=>opted((string)$r['interests'],$topic)&&safeEmail((string)$r['email'])));
}
function unsubscribeUrl(int $id,string $email,string $key): string {
 $sig=hash_hmac('sha256',$id.'|'.$email,$key);
 return 'https://tennis-schiri.de/newsletter.php?action=unsubscribe-signed&id='.$id.'&sig='.$sig;
}
$topics=['turniere'=>'Turniere & freie Plätze','regeln'=>'Regelwissen & Tipps','workshops'=>'Regelworkshops & Veranstaltungen'];
$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method==='POST'){
 if(!hash_equals((string)$_SESSION['csrf'],(string)($_POST['csrf']??''))){http_response_code(403);page('<section class="panel">Sitzung abgelaufen oder Anfrage ungültig.</section>');}
 $action=(string)($_POST['action']??'');
 $id=filter_var($_POST['campaign']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
 $now=time();
 if($action==='create'||$action==='save'){
  $subject=trim((string)($_POST['subject']??''));
  $body=trim((string)($_POST['body']??''));
  $topic=(string)($_POST['topic']??'');
  $headline=trim((string)($_POST['headline']??''));
  $preheader=trim((string)($_POST['preheader']??''));
  $buttonLabel=trim((string)($_POST['button_label']??''));
  $buttonUrl=trim((string)($_POST['button_url']??''));
  $eventTitle=trim((string)($_POST['event_title']??''));
  $eventDate=trim((string)($_POST['event_date']??''));
  $eventLocation=trim((string)($_POST['event_location']??''));
  $rawChosen=$_POST['selected_events']??[];
  if(!is_array($rawChosen) || count($rawChosen)>25 ||
     count(array_filter($rawChosen,'is_string'))!==count($rawChosen)){
    page('<section class="panel warning">Ungültige Terminauswahl. Bitte Seite neu laden.</section>');
  }
  $previousSelection='[]';
  if($action==='save'){
    $lookup=$db->prepare("SELECT selected_events FROM newsletter_campaigns WHERE id=? AND state='draft'");
    $lookup->execute([$id]);
    $old=$lookup->fetchColumn();
    if($old===false)page('<section class="panel warning">Entwurf nicht gefunden oder bereits freigegeben.</section>');
    $previousSelection=(string)$old;
  }
  $selectedEvents=newsletterResolveSelection($rawChosen,$previousSelection);
  if(count($selectedEvents)!==count(array_unique($rawChosen))){
    page('<section class="panel warning">Ein ausgewählter Termin ist nicht mehr verfügbar. Bitte den Entwurf neu laden und die Auswahl prüfen.</section>');
  }
  $selectedJson=json_encode($selectedEvents,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
  $rawPartners=$_POST['selected_partners']??[];
  if(!is_array($rawPartners)||count($rawPartners)>12||
     count(array_filter($rawPartners,'is_string'))!==count($rawPartners)){
    page('<section class="panel warning">Ungültige Partnerauswahl. Bitte die Seite neu laden.</section>');
  }
  $previousPartners='[]';
  if($action==='save'){
    $lookup=$db->prepare("SELECT selected_partners FROM newsletter_campaigns WHERE id=? AND state='draft'");
    $lookup->execute([$id]);
    $old=$lookup->fetchColumn();
    if($old===false)page('<section class="panel warning">Der Entwurf wurde bereits freigegeben oder existiert nicht mehr.</section>');
    $previousPartners=(string)$old;
  }
  $selectedPartners=newsletterResolvePartners($rawPartners,$previousPartners);
  if(count($selectedPartners)!==count(array_unique($rawPartners))){
    page('<section class="panel warning">Die gewählte Partnerliste hat sich geändert. Bitte die Seite neu laden.</section>');
  }
  $partnersJson=json_encode($selectedPartners,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
  if ($subject===''||strlen($subject)>160||preg_match('/[\r\n]/',$subject)||
      ($body==='' && count($selectedEvents)===0)||strlen($body)>10000||!array_key_exists($topic,$topics)||
      strlen($headline)>120||strlen($preheader)>160||strlen($buttonLabel)>70||
      !validButtonUrl($buttonUrl)||(($buttonLabel==='')!==($buttonUrl===''))||
      strlen($eventTitle)>120||strlen($eventDate)>80||strlen($eventLocation)>140||
      ($eventTitle==='' && ($eventDate!=='' || $eventLocation!==''))) {
    page('<section class="panel warning">Bitte Betreff, Text, Überschrift, Vorschautext und Button prüfen. Ein Button braucht sowohl Text als auch eine gültige HTTPS-Adresse.</section>');
  }
  if($action==='create'){
   $q=$db->prepare("INSERT INTO newsletter_campaigns(subject,body,topic,headline,preheader,button_label,button_url,event_title,event_date,event_location,selected_events,selected_partners,state,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,'draft',?,?)");
   $q->execute([$subject,$body,$topic,$headline,$preheader,$buttonLabel,$buttonUrl,$eventTitle,$eventDate,$eventLocation,$selectedJson,$partnersJson,$now,$now]);
   finish('Entwurf gespeichert.',(int)$db->lastInsertId());
  }
  $q=$db->prepare("UPDATE newsletter_campaigns SET subject=?,body=?,topic=?,headline=?,preheader=?,button_label=?,button_url=?,event_title=?,event_date=?,event_location=?,selected_events=?,selected_partners=?,updated_at=? WHERE id=? AND state='draft'");
  $q->execute([$subject,$body,$topic,$headline,$preheader,$buttonLabel,$buttonUrl,$eventTitle,$eventDate,$eventLocation,$selectedJson,$partnersJson,$now,$id]);
  finish($q->rowCount()?'Änderungen gespeichert.':'Nur unveröffentlichte Entwürfe können bearbeitet werden.',$id);
 }
 if(!$id)finish('Ungültige Kampagne.');
 $q=$db->prepare('SELECT * FROM newsletter_campaigns WHERE id=?');$q->execute([$id]);$campaign=$q->fetch();
 if(!$campaign)finish('Kampagne nicht gefunden.');
 if($action==='test'){
  if($campaign['state']!=='draft')finish('Testsendung nur vor Freigabe möglich.',$id);
  $unsubPreview='https://tennis-schiri.de/newsletter.php?action=unsubscribe-request';
  $subject='[TEST] '.$campaign['subject'];
  $text="TESTNACHRICHT – kein Versand an Abonnenten.\n\n".newsletterPlainText($campaign,$unsubPreview);
  $html=newsletterTemplate($campaign,$unsubPreview,true);
  $ok=sendMail('mw@tennis-schiri.de',$subject,$text,$html);
  finish($ok?'Testmail an mw@tennis-schiri.de vom Server angenommen.':'Testmail wurde vom Server abgelehnt.',$id);
 }
 if($action==='approve'){
  if($campaign['state']!=='draft'||($_POST['confirmation']??'')!=='SENDEN')finish('Freigabe nicht durchgeführt.',$id);
  $people=recipients($db,$campaign['topic']);
  if(count($people)===0)finish('Keine bestätigten Kontakte mit passendem Interesse.',$id);
  try {
   $db->exec('BEGIN IMMEDIATE');
   $q=$db->prepare("INSERT OR IGNORE INTO newsletter_deliveries(campaign_id,subscriber_id,recipient_key,state) VALUES(?,?,?,'pending')");
   foreach($people as $person)$q->execute([$id,(int)$person['id'],hash_hmac('sha256',strtolower((string)$person['email']),$cfg['key'])]);
   $db->prepare("UPDATE newsletter_campaigns SET state='ready',updated_at=? WHERE id=? AND state='draft'")->execute([$now,$id]);
   $db->commit();
  }catch(Throwable $e){if($db->inTransaction())$db->rollBack();error_log('Newsletter approve failed: '.$e->getMessage());finish('Freigabe fehlgeschlagen.',$id);}
  finish('Versand freigegeben für '.count($people).' passende bestätigte Kontakte. Noch keine E-Mails verschickt.',$id);
 }
 if($action==='send'){
  if(!in_array($campaign['state'],['ready','sending'],true))finish('Versand nicht freigegeben.',$id);
  $sent=0;$failed=0;$skipped=0;
  for($i=0;$i<5;$i++){
   try {
    $db->exec('BEGIN IMMEDIATE');
    $q=$db->prepare("SELECT d.subscriber_id,d.recipient_key,s.email,s.interests,s.confirmed_at FROM newsletter_deliveries d LEFT JOIN subscribers s ON s.id=d.subscriber_id WHERE d.campaign_id=? AND d.state='pending' ORDER BY d.subscriber_id LIMIT 1");
    $q->execute([$id]);$r=$q->fetch();
    if(!$r){$db->commit();break;}
    $db->prepare("UPDATE newsletter_deliveries SET state='sending',attempted_at=? WHERE campaign_id=? AND subscriber_id=? AND state='pending'")->execute([time(),$id,(int)$r['subscriber_id']]);
    $db->prepare("UPDATE newsletter_campaigns SET state='sending',updated_at=? WHERE id=?")->execute([time(),$id]);
    $db->commit();
   }catch(Throwable $e){if($db->inTransaction())$db->rollBack();error_log('Newsletter queue claim failed: '.$e->getMessage());finish('Warteschlange konnte nicht verarbeitet werden.',$id);}
   $valid=$r['confirmed_at']!==null&&is_string($r['email'])&&safeEmail($r['email'])&&hash_equals((string)$r['recipient_key'],hash_hmac('sha256',strtolower($r['email']),$cfg['key']))&&opted((string)$r['interests'],$campaign['topic']);
   if(!$valid){
    $db->prepare("UPDATE newsletter_deliveries SET state='skipped' WHERE campaign_id=? AND subscriber_id=?")->execute([$id,(int)$r['subscriber_id']]);
    $skipped++;continue;
   }
   $url=unsubscribeUrl((int)$r['subscriber_id'],$r['email'],$cfg['key']);
   $text=newsletterPlainText($campaign,$url);
   $html=newsletterTemplate($campaign,$url);
   $ok=sendMail($r['email'],$campaign['subject'],$text,$html,$url);
   $db->prepare("UPDATE newsletter_deliveries SET state=? WHERE campaign_id=? AND subscriber_id=? AND state='sending'")->execute([$ok?'accepted':'failed',$id,(int)$r['subscriber_id']]);
   if($ok)$sent++;else $failed++;
  }
  $q=$db->prepare("SELECT COUNT(*) FROM newsletter_deliveries WHERE campaign_id=? AND state='pending'");$q->execute([$id]);
  $remaining=(int)$q->fetchColumn();
  if($remaining===0) $db->prepare("UPDATE newsletter_campaigns SET state='finished',updated_at=? WHERE id=?")->execute([time(),$id]);
  finish("Chargenversand: $sent vom Server angenommen, $failed abgelehnt, $skipped übersprungen, $remaining noch ausstehend.",$id);
 }
 finish('Unbekannte Aktion.',$id);
}
$notice=isset($_GET['info'])?'<p class="success">'.h(substr((string)$_GET['info'],0,300)).'</p>':'';
$id=filter_var($_GET['campaign']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
if($id){
 $q=$db->prepare('SELECT * FROM newsletter_campaigns WHERE id=?');$q->execute([$id]);$c=$q->fetch();
 if(!$c)page('<section class="panel">Kampagne nicht gefunden.</section>');
 if(($_GET['preview']??'')==='1'){
  header('Content-Type: text/html; charset=UTF-8');
  header('Content-Disposition: inline; filename="tennis-schiri-newsletter-vorschau.html"');
  echo newsletterTemplate($c,'https://tennis-schiri.de/newsletter.php?action=unsubscribe-request',true);
  exit;
 }

 $q=$db->prepare('SELECT state,COUNT(*) AS n FROM newsletter_deliveries WHERE campaign_id=? GROUP BY state');$q->execute([$id]);
 $counts=[];foreach($q->fetchAll() as $r)$counts[$r['state']]=(int)$r['n'];
 $state=$c['state'];
 $n=count(recipients($db,$c['topic']));
 $body='<section class="panel"><h2>Kampagne #'.(int)$c['id'].'</h2>'.$notice.'<p><strong>Status:</strong> '.h($state).' · <strong>Thema:</strong> '.h($topics[$c['topic']]??$c['topic']).' · <strong>Aktuell passende bestätigte Kontakte:</strong> '.$n.'</p>';
 if($state==='draft'){
  $body.='<form method="post">'.csrf().'<input type="hidden" name="action" value="save"><input type="hidden" name="campaign" value="'.$id.'"><label>Betreff</label><input type="text" maxlength="160" name="subject" value="'.h($c['subject']).'" required><label>Thema / Empfängerkreis</label><select name="topic">';
  foreach($topics as $key=>$name)$body.='<option value="'.h($key).'"'.($c['topic']===$key?' selected':'').'>'.h($name).'</option>';
  $body.='</select>'.extraFields($c).'<label>Einleitung / Nachrichtentext (optional bei gewählten Terminen)</label><textarea name="body" maxlength="10000">'.h($c['body']).'</textarea><small>Automatisch gestaltete Absätze, Textalternative und persönlicher Abmeldelink.</small><p><button type="submit">Entwurf speichern</button> <a href="?campaign='.$id.'&amp;preview=1" target="_blank" rel="noopener">HTML-Vorschau öffnen ↗</a></p></form><form method="post">'.csrf().'<input type="hidden" name="action" value="test"><input type="hidden" name="campaign" value="'.$id.'"><button type="submit">Testmail nur an mw@tennis-schiri.de</button></form>';
  $body.='<div class="warning"><strong>Versandfreigabe:</strong> Erst eine Testmail prüfen. Bei Freigabe wird eine feste Empfängerliste der zu diesem Zeitpunkt bestätigten Interessenten erstellt. Eine spätere Abmeldung wird vor jedem Sendeversuch erneut berücksichtigt.</div><form method="post">'.csrf().'<input type="hidden" name="action" value="approve"><input type="hidden" name="campaign" value="'.$id.'"><label>Zum Freigeben SENDEN eintippen</label><input type="text" name="confirmation" autocomplete="off" placeholder="SENDEN" required><button type="submit">Empfängerliste freigeben – noch nicht verschicken</button></form>';
 }else{
  $body.='<h3>'.h($c['subject']).'</h3><p><a href="?campaign='.$id.'&amp;preview=1" target="_blank" rel="noopener">HTML-Ansicht öffnen ↗</a></p><pre style="white-space:pre-wrap;font:inherit">'.h($c['body']).'</pre><p>Versandstatistik: '.h(json_encode($counts,JSON_UNESCAPED_UNICODE)).'</p><p class="muted">„Vom Server angenommen“ bestätigt keine Zustellung im Postfach. Bei Verbindungsabbruch können Einträge im Zustand „sending“ bleiben; diese werden zur Vermeidung unbemerkter Doppelversendungen nicht automatisch erneut versendet.</p>';
  if(in_array($state,['ready','sending'],true))$body.='<form method="post">'.csrf().'<input type="hidden" name="action" value="send"><input type="hidden" name="campaign" value="'.$id.'"><button type="submit">Nächste maximal 5 E-Mails versenden</button></form>';
 }
 $body.='</section>';
 page($body);
}
$all=$db->query('SELECT id,subject,topic,state,created_at FROM newsletter_campaigns ORDER BY id DESC LIMIT 50')->fetchAll();
$body='<section class="panel"><h2>Neuen E-Mail-Entwurf anlegen</h2>'.$notice.'<p>Erstelle eine gestaltete Newsletter-Ausgabe. Speichern, HTML-Vorschau ansehen, Testmail an mw@tennis-schiri.de prüfen und erst danach den Versand freigeben. Der Versand startet niemals automatisch.</p><form method="post">'.csrf().'<input type="hidden" name="action" value="create"><label>Betreff</label><input type="text" name="subject" maxlength="160" required><label>Interessengebiet</label><select name="topic">';
foreach($topics as $key=>$name)$body.='<option value="'.h($key).'">'.h($name).'</option>';
$body.='</select>'.extraFields().'<label>Einleitung / Nachrichtentext (optional bei gewählten Terminen)</label><textarea name="body" maxlength="10000" placeholder="Deine Neuigkeiten: Leerzeilen erzeugen Absätze."></textarea><p><button type="submit">Als Entwurf speichern</button></p></form></section>';
$body.='<section class="panel"><h2>Letzte Kampagnen</h2><table><thead><tr><th>Betreff</th><th>Thema</th><th>Status</th></tr></thead><tbody>';
foreach($all as $row)$body.='<tr><td><a href="?campaign='.(int)$row['id'].'">'.h($row['subject']).'</a></td><td>'.h($topics[$row['topic']]??$row['topic']).'</td><td>'.h($row['state']).'</td></tr>';
$body.='</tbody></table></section>';
page($body);
