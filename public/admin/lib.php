<?php
declare(strict_types=1);

/* Tennis-Schiri Backoffice: private helper library, never a public endpoint. */
if (!defined('TS_BACKOFFICE')) { http_response_code(404); exit; }
require_once __DIR__.'/info-sections.php';
require_once __DIR__.'/venues.php';
require_once __DIR__.'/guide.php';
require_once __DIR__.'/defaults.php';
require_once __DIR__.'/weather-mail.php';

function boEsc(mixed $value): string {
 return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function boPost(string $field, int $max=10000): string {
 $v=$_POST[$field]??'';
 return is_string($v) ? substr(trim($v),0,$max+1) : '';
}
function boSecureHeaders(): void {
 header('X-Content-Type-Options: nosniff');
 header('X-Frame-Options: DENY');
 header('Referrer-Policy: no-referrer');
 header('Cache-Control: private, no-store, max-age=0');
 header('X-Robots-Tag: noindex, nofollow, noarchive');
 header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
}
function boStart(): array {
 boSecureHeaders();
 $configFile=dirname(__DIR__,2).'/tennis-schiri-newsletter-config.php';
 if(!is_file($configFile)){http_response_code(503);exit('Backoffice noch nicht eingerichtet.');}
 $cfg=require $configFile;
 if(!is_array($cfg)||($cfg['enabled']??false)!==true||empty($cfg['database'])||empty($cfg['admin_password_hash'])){
  http_response_code(503);exit('Backoffice-Konfiguration nicht verfügbar.');
 }
 session_name('ts_newsletter_admin');
 session_set_cookie_params(['httponly'=>true,'secure'=>true,'samesite'=>'Strict','path'=>'/']);
 session_start();
 if(empty($_SESSION['authenticated'])||(int)($_SESSION['auth_at']??0)<time()-8*3600){
  $_SESSION=[];session_regenerate_id(true);
  header('Location: /newsletter-admin.php',true,303);exit;
 }
 if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(24));
 try{
  $db=new PDO('sqlite:'.$cfg['database'],null,null,[
   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
   PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
  ]);
  $db->exec('PRAGMA busy_timeout=5000');
  boSchema($db);
 }catch(Throwable $e){
  error_log('TS Backoffice DB: '.$e->getMessage());
  http_response_code(503);exit('Verwaltungsspeicher vorübergehend nicht verfügbar.');
 }
 return [$cfg,$db];
}
function boSchema(PDO $db): void {
 $db->exec("CREATE TABLE IF NOT EXISTS bo_contacts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tournament_id TEXT NOT NULL,
  name TEXT NOT NULL DEFAULT '',
  email TEXT NOT NULL COLLATE NOCASE,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  UNIQUE(tournament_id,email)
 )");
 $db->exec("CREATE INDEX IF NOT EXISTS bo_contacts_tournament ON bo_contacts(tournament_id)");
 $db->exec("CREATE TABLE IF NOT EXISTS bo_campaigns (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tournament_id TEXT NOT NULL,
  tournament_name TEXT NOT NULL,
  tournament_date TEXT NOT NULL DEFAULT '',
  tournament_location TEXT NOT NULL DEFAULT '',
  tournament_url TEXT NOT NULL,
  template_type TEXT NOT NULL DEFAULT 'info',
  subject TEXT NOT NULL,
  body TEXT NOT NULL,
  notes TEXT NOT NULL DEFAULT '',
  state TEXT NOT NULL DEFAULT 'draft',
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  approved_at INTEGER
 )");
 $campaignColumns=array_column($db->query('PRAGMA table_info(bo_campaigns)')->fetchAll(),'name');
 if(!in_array('notes',$campaignColumns,true))$db->exec("ALTER TABLE bo_campaigns ADD COLUMN notes TEXT NOT NULL DEFAULT ''");

 foreach ([
  'info_sections'=>"TEXT NOT NULL DEFAULT '[]'",
  'info_arrival'=>"TEXT NOT NULL DEFAULT ''",
  'info_schedule'=>"TEXT NOT NULL DEFAULT ''",
  'info_parking'=>"TEXT NOT NULL DEFAULT ''",
  'info_fees'=>"TEXT NOT NULL DEFAULT ''",
  'turnigo_url'=>"TEXT NOT NULL DEFAULT ''",
  'myscore_url'=>"TEXT NOT NULL DEFAULT ''",
  'info_contact'=>"TEXT NOT NULL DEFAULT ''",
  'venue_id'=>"TEXT NOT NULL DEFAULT 'general'",
  'venue_data'=>"TEXT NOT NULL DEFAULT '{}'",
  'guide_data'=>"TEXT NOT NULL DEFAULT '{}'",
  'defaults_review_required'=>'INTEGER NOT NULL DEFAULT 0',
  'defaults_reviewed_at'=>'INTEGER',
  'weather_enabled'=>'INTEGER NOT NULL DEFAULT 1',
 ] as $column=>$definition){
  if(!in_array($column,$campaignColumns,true))$db->exec('ALTER TABLE bo_campaigns ADD COLUMN '.$column.' '.$definition);
 }

 $db->exec("CREATE TABLE IF NOT EXISTS bo_deliveries (
  campaign_id INTEGER NOT NULL,
  contact_id INTEGER NOT NULL,
  email TEXT NOT NULL,
  name TEXT NOT NULL DEFAULT '',
  state TEXT NOT NULL DEFAULT 'pending',
  attempted_at INTEGER,
  PRIMARY KEY(campaign_id,contact_id)
 )");
 $db->exec("CREATE INDEX IF NOT EXISTS bo_deliveries_campaign ON bo_deliveries(campaign_id,state)");
 boVenueSchema($db);
}
function boCsrf(): string {
 return '<input type="hidden" name="csrf" value="'.boEsc($_SESSION['csrf']??'').'">';
}
function boCheckPost(): void {
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!hash_equals((string)($_SESSION['csrf']??''),boPost('csrf',128))){
  http_response_code(403);exit('Ungültige oder abgelaufene Anfrage.');
 }
}
function boUrl(string $view='dashboard',int $id=0,string $info=''): string {
 $parts=['view'=>$view];
 if($id>0)$parts['id']=$id;
 if($info!=='')$parts['info']=$info;
 return '/admin/?'.http_build_query($parts,'','&',PHP_QUERY_RFC3986);
}
function boGo(string $view='dashboard',int $id=0,string $info=''): never {
 header('Location: '.boUrl($view,$id,$info),true,303);exit;
}
function boGoContacts(string $tournament,string $info): never {
 header('Location: '.boUrl('contacts',0,$info).'&tournament='.rawurlencode($tournament),true,303);exit;
}
function boError(string $msg,int $code=422): never {
 http_response_code($code);
 boPage('Eingabe prüfen','<section class="panel"><p>'.boEsc($msg).'</p><p><a href="/admin/">Zum Backoffice</a></p></section>');
}
function boPage(string $title,string $content): never {
 header('Content-Type: text/html; charset=UTF-8');
 $e=static fn($x)=>boEsc($x);
 $view=(string)($_GET['view']??'dashboard');
 $tabs=[
  'dashboard'=>['Übersicht','layout-dashboard'],
  'contacts'=>['Teilnehmer','users'],
  'compose'=>['Turnier-Infomails','mail'],
  'templates'=>['Vorlagen','files'],
  'venues'=>['Standorte','map-pin'],
  'history'=>['Versandprotokoll','history']
 ];
 $nav='';
 foreach($tabs as $name=>$entry) {
  $isActive=$view===$name||($view==='campaign'&&$name==='compose');
  $nav.='<a href="'.boUrl($name).'"'.($isActive?' aria-current="page" class="active"':'').'>'.$e($entry[0]).'</a>';
 }
 $notice='';
 $note=(string)($_GET['info']??'');
 if($note!==''&&strlen($note)<400)$notice='<div class="notice" role="status">'.$e($note).'</div>';
 echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#142b29"><title>'.$e($title).' · Tennis-Schiri Backoffice</title><style>
 :root{color-scheme:light;--dark:#143629;--green:#286244;--lime:#c8ee77;--paper:#f3f6f0;--stroke:#d9e5d4;--muted:#617365}
 *{box-sizing:border-box}body{margin:0;font:15px/1.55 system-ui,-apple-system,"Segoe UI",sans-serif;background:var(--paper);color:#183c2b}
 a{color:#236d47}a:hover{text-decoration-thickness:2px}header{background:var(--dark);padding:20px max(20px,calc((100vw - 1190px)/2));color:white}
 .head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
 .head img{width:250px;max-width:55vw;height:auto;display:block}header small{color:#d7e7cf;font-size:12px;font-weight:700;letter-spacing:.06em}
 nav{display:flex;flex-wrap:wrap;gap:7px;margin-top:21px}nav a{color:#e2ebe0;text-decoration:none;border:1px solid #52715b;border-radius:9px;padding:9px 13px;font-size:13px;font-weight:700}
 nav a.active,nav a:hover{background:var(--lime);border-color:var(--lime);color:#173a29}
 main{max-width:1200px;margin:28px auto;padding:0 18px 70px}
 h1{font-size:clamp(27px,3.5vw,39px);letter-spacing:-.04em;margin:0 0 8px}h2{font-size:22px;letter-spacing:-.025em;margin:0 0 13px}h3{font-size:16px;margin:0 0 10px}
 p{margin:8px 0 15px}small,.muted{color:var(--muted)}.intro{color:#5b7162;max-width:770px;margin-bottom:26px}
 .panel,.tile{background:white;border:1px solid var(--stroke);border-radius:15px;padding:23px;margin-bottom:17px;box-shadow:0 5px 20px #19382907}
 .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(225px,1fr));gap:15px}.tile{margin:0}
 .tile h3{margin:12px 0 4px;color:#19442e;font-size:18px}.tile p{font-size:13px;color:var(--muted)}
 .tile a{font-weight:800;font-size:13px}.stat{font-size:30px;font-weight:900;letter-spacing:-.06em;color:var(--dark);line-height:1.2}
 .notice{background:#e6f2dc;border:1px solid #c4dfb0;padding:13px 17px;border-radius:9px;margin:0 0 20px}
 .warning{background:#fff4e5;border-left:4px solid #df9950;padding:11px 14px;border-radius:6px}
 .error{color:#9f3128}
 label{display:block;font-weight:750;margin:15px 0 6px}
 input[type=text],input[type=email],input[type=url],input[type=file],textarea,select{display:block;width:100%;font:inherit;color:#193c2c;background:#fff;padding:11px 12px;border:1px solid #b6cbb9;border-radius:9px;max-width:100%}
 input[type=checkbox]{accent-color:#27633d}textarea{min-height:210px;resize:vertical}
 button,.button{display:inline-block;background:#193d2d;color:white;border:0;border-radius:9px;padding:11px 17px;font:inherit;font-weight:750;cursor:pointer;text-decoration:none}
 button:hover,.button:hover{background:#285943;color:white;text-decoration:none}.button-secondary{background:#e8f2df;color:#244a32;border:1px solid #c9ddc0}
 .button-secondary:hover{background:#d4eac8;color:#183b26}.button-danger{background:#a34837}.button-danger:hover{background:#803524}
 .row{display:flex;align-items:center;flex-wrap:wrap;gap:10px}.row-between{justify-content:space-between}
 table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:11px 10px;text-align:left;border-bottom:1px solid #e1eae0;vertical-align:top}th{font-weight:800;background:#f6f9f3}.tablewrap{overflow:auto}
 .badge{display:inline-block;font-weight:800;border-radius:99px;background:#e7f2de;color:#28533a;padding:4px 9px;font-size:11px;white-space:nowrap}
 .badge.gray{background:#edf0eb;color:#60706a}.badge.orange{background:#fff0d8;color:#966122}
 .inline{display:inline-block;margin:0}.help{font-size:12px;color:#637464;line-height:1.6}
 .split{display:grid;grid-template-columns:1fr 1fr;gap:17px}
 footer{max-width:1200px;margin:0 auto;padding:0 18px 30px;color:#6b7f70;font-size:12px}
 @media(max-width:700px){.split{grid-template-columns:1fr}.panel{padding:16px}header{padding:18px}.head img{width:200px}table{font-size:12px}}
 </style></head><body><header><div class="head"><a href="/admin/" aria-label="Backoffice"><img src="/assets/branding/ts-logo-dunkel.png?v=20261107-3" alt="Tennis-Schiri"></a><small>BACKOFFICE · INTERNE VERWALTUNG</small></div><nav aria-label="Backoffice-Navigation">'.$nav.'</nav></header>
 <main><div class="row row-between"><div><h1>'.$e($title).'</h1><p class="intro">Organisation und Kommunikation für Tennis-Schiri</p></div><div class="row"><a href="/newsletter-admin.php">Newsletter-Abonnenten</a><a href="/newsletter-mail.php">Newsletter-Versand</a><form class="inline" action="/newsletter-admin.php" method="post">'.boCsrf().'<input type="hidden" name="action" value="logout"><button class="button-secondary" type="submit">Abmelden</button></form></div></div>'.$notice.$content.'</main><footer>Internes Backoffice · Empfänger für Turnier-Infomails werden getrennt von Newsletter-Abonnenten gespeichert. Kein automatischer Versand.</footer></body></html>';
 exit;
}
function boTournaments(): array {
 if(!defined('TS_NEWSLETTER_EVENTS'))define('TS_NEWSLETTER_EVENTS',true);
 require_once dirname(__DIR__).'/newsletter-events.php';
 $entries=newsletterEventsCatalog();
 $out=[];
 foreach($entries as $e)if(($e['type']??'')==='turnier')$out[$e['id']]=$e;
 return $out;
}
function boTournamentSelect(array $tournaments,string $selected=''): string {
 $e=static fn($s)=>boEsc($s);
 $out='<select name="tournament" required><option value="">Turnier wählen …</option>';
 foreach($tournaments as $id=>$t){
  $label=$t['date'].' · '.$t['title'].' · '.$t['location'];
  $out.='<option value="'.$e($id).'"'.($id===$selected?' selected':'').'>'.$e($label).'</option>';
 }
 return $out.'</select>';
}
function boTemplates(): array {
 return [
  'info'=>['label'=>'Ausführlicher Turnier-Guide nach Auslosung','subject'=>'🎾 Alle Informationen: {{turnier}}',
   'body'=>"Hallo {{name}},\n\nwir freuen uns, dich bei {{turnier}} begrüßen zu dürfen. Im Folgenden findest du die wichtigsten Informationen rund um deine Teilnahme.\n\n{{zusatz}}\n\nSportliche Grüße\nMichael"],
  'reminder'=>['label'=>'Kurze Erinnerung vor dem Turnier','subject'=>'⏰ Erinnerung: {{turnier}}',
   'body'=>"Hallo {{name}},\n\nbald ist es soweit: {{turnier}} steht bevor. Bitte prüfe vor deiner Anreise die aktuellen Turnierinformationen und deine Spielansetzung.\n\n{{zusatz}}\n\nBis bald auf dem Tennisplatz!\nMichael"],
  'update'=>['label'=>'Wichtige organisatorische Änderung','subject'=>'📣 Wichtige Änderung: {{turnier}}',
   'body'=>"Hallo {{name}},\n\nes gibt eine wichtige organisatorische Information zu {{turnier}}. Bitte beachte die nachfolgenden Hinweise.\n\n{{zusatz}}\n\nSportliche Grüße\nMichael"],
 ];
}
function boRenderTokens(string $raw,array $campaign,array $contact): string {
 $map=[
  '{{name}}'=>trim((string)($contact['name']??''))?:'Tennisfreundin/Tennisfreund',
  '{{turnier}}'=>(string)$campaign['tournament_name'],
  '{{datum}}'=>(string)$campaign['tournament_date'],
  '{{ort}}'=>(string)$campaign['tournament_location'],
  '{{turnier_link}}'=>(string)$campaign['tournament_url'],
  '{{zusatz}}'=>(string)($campaign['notes']??'')
 ];
 return strtr($raw,$map);
}
function boMailContent(array $campaign,array $contact,bool $preview=false): array {
 $subject=boRenderTokens((string)$campaign['subject'],$campaign,$contact);
 $body=boRenderTokens((string)$campaign['body'],$campaign,$contact);
 $esc=static fn($value)=>boEsc($value);
 $htmlBody='';
 foreach(preg_split('/\R{2,}/u',trim($body))?:[] as $part) {
  $htmlBody.='<p style="color:#315641;font-size:15px;line-height:1.8;margin:0 0 19px;">'.nl2br($esc(trim($part)),false).'</p>';
 }
 $link=(string)$campaign['tournament_url'];
 $button='';
 if(filter_var($link,FILTER_VALIDATE_URL)&&parse_url($link,PHP_URL_SCHEME)==='https'){
  $button='<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:23px 0 28px"><tr><td bgcolor="#c8ef79" style="background:#c8ef79;border-radius:8px"><a href="'.$esc($link).'" style="display:block;padding:14px 24px;color:#153b29;font-size:14px;font-weight:bold;text-decoration:none;">Turnierdetails ansehen &#8599;</a></td></tr></table>';
 }
 [$infoHtml,$infoPlain]=boInfoMailBlocks($campaign);
 [$venueHtml,$venuePlain]=boVenueMailBlocks($campaign,$preview);
 [$guideHtml,$guidePlain]=boGuideMailBlocks($campaign);
 [$weatherHtml,$weatherPlain]=boWeatherMailBlocks($campaign);
 $warning=$preview?'<tr><td bgcolor="#fff0ce" style="padding:12px 18px;text-align:center;font-weight:bold;color:#5f4822">TEST / VORSCHAU – kein Versand an Teilnehmer</td></tr>':'';
 $html='<!doctype html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$esc($subject).'</title></head><body style="margin:0;background:#f0f4ec;font-family:Arial,Helvetica,sans-serif;color:#183d2c"><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f0f4ec;padding:28px 10px"><tr><td align="center"><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:100%;max-width:600px;background:#fff;border-radius:15px"><tr><td bgcolor="#15392b" style="background:#15392b;padding:24px 30px"><img src="https://tennis-schiri.de/assets/branding/ts-logo-dunkel.png?v=20261107-3" alt="Tennis-Schiri" width="240" style="display:block;width:240px;height:auto;max-width:100%;border:0;"></td></tr><tr><td bgcolor="#c8ef79" height="5" style="background:#c8ef79;font-size:1px;line-height:5px">&nbsp;</td></tr>'.$warning.'<tr><td style="padding:35px 32px 25px"><p style="margin:0 0 12px;font-size:11px;font-weight:800;letter-spacing:1.3px;color:#4b8a5e;">TURNIERINFORMATION</p><h1 style="margin:0 0 24px;color:#173b29;font-size:30px;line-height:1.15;">'.$esc($campaign['tournament_name']).'</h1><table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#f1f7e8;border:1px solid #daebd1;margin:0 0 25px"><tr><td style="padding:15px;vertical-align:top"><p style="font-size:10px;letter-spacing:1px;font-weight:800;color:#4c8258;margin:0 0 5px">TERMIN</p><p style="margin:0;font-size:15px;font-weight:800;color:#1c4830">'.$esc($campaign['tournament_date']).'</p></td><td style="padding:15px;vertical-align:top"><p style="font-size:10px;letter-spacing:1px;font-weight:800;color:#4c8258;margin:0 0 5px">SPIELORT</p><p style="margin:0;font-size:15px;font-weight:800;color:#1c4830">'.$esc($campaign['tournament_location']).'</p></td></tr></table>'.$htmlBody.$venueHtml.$guideHtml.$infoHtml.$weatherHtml.$button.'</td></tr><tr><td style="padding:23px 31px;background:#f5f9f0;border-top:1px solid #dfe8d9"><p style="margin:0;color:#5d735f;font-size:12px;line-height:1.8">Organisatorische Nachricht zu deiner Turnierteilnahme – kein Werbe-Newsletter.<br>Tennis-Schiri · Michael Wiedeck · Neuenhagen bei Berlin<br>Antworten: <a style="color:#2c6c44" href="mailto:turniere@tennis-schiri.de">turniere@tennis-schiri.de</a><br><a style="color:#2c6c44" href="https://tennis-schiri.de/impressum/">Impressum</a> · <a style="color:#2c6c44" href="https://tennis-schiri.de/datenschutz/">Datenschutz</a></p></td></tr></table></td></tr></table></body></html>';
 $text=$subject."\n\n".$body.$venuePlain.$guidePlain.$infoPlain.$weatherPlain."\n\n---\nOrganisatorische Nachricht zu deiner Turnierteilnahme – kein Werbe-Newsletter.\nTennis-Schiri · turniere@tennis-schiri.de\nImpressum: https://tennis-schiri.de/impressum/\nDatenschutz: https://tennis-schiri.de/datenschutz/\n";
 return [$subject,$text,$html];
}
function boSafeEmail(string $email): bool {
 return strlen($email)<=254 && filter_var($email,FILTER_VALIDATE_EMAIL)!==false && !str_contains($email,"\r") && !str_contains($email,"\n");
}
function boSend(string $to,string $subject,string $text,string $html): bool {
 if(!boSafeEmail($to)||$subject===''||strlen($subject)>240||preg_match('/[\r\n]/',$subject))return false;
 $boundary='bo_'.bin2hex(random_bytes(14));
 $headers=[
  'From: Tennis-Schiri Turnierorganisation <turniere@tennis-schiri.de>',
  'Reply-To: turniere@tennis-schiri.de',
  'MIME-Version: 1.0',
  'Content-Type: multipart/alternative; boundary="'.$boundary.'"',
  'X-Auto-Response-Suppress: All',
 ];
 $message="This is a multipart message in MIME format.\r\n";
 foreach([['text/plain',$text],['text/html',$html]] as [$mime,$value]){
  $message.="--".$boundary."\r\nContent-Type: ".$mime."; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
  $message.=chunk_split(base64_encode($value),76,"\r\n");
 }
 $message.="--".$boundary."--\r\n";
 $encoded='=?UTF-8?B?'.base64_encode($subject).'?=';
 return mail($to,$encoded,$message,implode("\r\n",$headers),'-fturniere@tennis-schiri.de');
}
function boCampaign(PDO $db,int $id): ?array {
 $q=$db->prepare('SELECT * FROM bo_campaigns WHERE id=?');$q->execute([$id]);
 $row=$q->fetch();
 return $row?:null;
}
function boStatus(string $state): string {
 return ['draft'=>'Entwurf','approved'=>'Freigegeben','sending'=>'Versand läuft','done'=>'Abgeschlossen','pending'=>'Wartend','attempted'=>'Versuch gestartet','sent'=>'Vom Mailserver angenommen','failed'=>'Fehlgeschlagen','skipped'=>'Übersprungen'][$state]??$state;
}
