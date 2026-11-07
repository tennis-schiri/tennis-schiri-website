<?php
declare(strict_types=1);

if (!defined('TS_WORKSHOP')) { http_response_code(404); exit; }

/** @return array<string,array<string,mixed>> */
function workshopCatalog(): array {
 return [
  'regelworkshop-2027-03-04'=>[
   'id'=>'regelworkshop-2027-03-04',
   'title'=>'Regelworkshop beim NTC 93',
   'date_iso'=>'2027-03-04',
   'date'=>'Donnerstag, 4. März 2027',
   'time'=>'19:00 Uhr',
   'location'=>'Neuenhagener Tennisclub 93 e. V.',
   'address'=>'Hildesheimer Straße 11–13, 15366 Neuenhagen bei Berlin',
   'host_club'=>'Neuenhagener Tennisclub 93 e. V.',
   'guest_fee_cents'=>1500,
  ],
 ];
}

function workshopSchema(PDO $db): void {
 $db->exec("CREATE TABLE IF NOT EXISTS workshop_registrations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  workshop_id TEXT NOT NULL,
  name TEXT NOT NULL,
  email TEXT NOT NULL COLLATE NOCASE,
  club TEXT NOT NULL DEFAULT '',
  is_member INTEGER NOT NULL DEFAULT 0,
  fee_cents INTEGER NOT NULL DEFAULT 1500,
  payment_method TEXT NOT NULL DEFAULT 'onsite',
  payment_status TEXT NOT NULL DEFAULT 'open',
  status TEXT NOT NULL DEFAULT 'registered',
  notes TEXT NOT NULL DEFAULT '',
  confirmation_sent_at INTEGER,
  last_mail_type TEXT NOT NULL DEFAULT '',
  last_mail_at INTEGER,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  UNIQUE(workshop_id,email)
 )");
 $db->exec("CREATE INDEX IF NOT EXISTS workshop_registrations_workshop ON workshop_registrations(workshop_id,status)");
 $db->exec("CREATE TABLE IF NOT EXISTS workshop_attempts (
  ip_hash TEXT NOT NULL,
  created_at INTEGER NOT NULL
 )");
 $db->exec("CREATE INDEX IF NOT EXISTS workshop_attempts_created ON workshop_attempts(created_at)");
 $db->exec("CREATE TABLE IF NOT EXISTS workshop_settings (
  setting_key TEXT PRIMARY KEY,
  setting_value TEXT NOT NULL DEFAULT '',
  updated_at INTEGER NOT NULL
 )");
}

function workshopSetting(PDO $db,string $key): string {
 $q=$db->prepare('SELECT setting_value FROM workshop_settings WHERE setting_key=?');
 $q->execute([$key]);
 $value=$q->fetchColumn();
 return $value===false?'':trim((string)$value);
}
function workshopSetSetting(PDO $db,string $key,string $value,int $now): void {
 $q=$db->prepare('INSERT INTO workshop_settings(setting_key,setting_value,updated_at) VALUES(?,?,?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value,updated_at=excluded.updated_at');
 $q->execute([$key,trim($value),$now]);
}
function workshopSafeEmail(string $email): bool {
 return strlen($email)<=254 && filter_var($email,FILTER_VALIDATE_EMAIL)!==false && !str_contains($email,"\r") && !str_contains($email,"\n");
}
function workshopFeeLabel(int $cents): string {
 return $cents<=0?'kostenfrei':number_format($cents/100,2,',','.').' €';
}
function workshopPaymentMethodLabel(string $value): string {
 return ['free'=>'kostenfrei','onsite'=>'vor Ort','paypal'=>'PayPal'][$value]??$value;
}
function workshopPaymentStatusLabel(string $value): string {
 return ['open'=>'offen','paid'=>'bezahlt','waived'=>'entfällt'][$value]??$value;
}

/** @return array{0:string,1:string,2:string} */
function workshopMailContent(array $workshop,array $registration,string $kind='confirmation',string $paypalInstructions=''): array {
 $name=trim((string)($registration['name']??'')) ?: 'Tennisfreundin/Tennisfreund';
 $fee=(int)($registration['fee_cents']??0);
 $payment=(string)($registration['payment_method']??'onsite');
 $isMember=(int)($registration['is_member']??0)===1;
 $event=$workshop['title'].' · '.$workshop['date'].' · '.$workshop['time'];
 $venue=$workshop['location'].', '.$workshop['address'];

 if($isMember || $fee<=0){
  $paymentText='Als Mitglied des NTC 93 nimmst du kostenfrei teil.';
 }elseif($payment==='paypal'){
  $paymentText='Teilnahmebeitrag: '.workshopFeeLabel($fee).' · Zahlung per PayPal.';
  $paymentText.=trim($paypalInstructions)!==''?"\nPayPal-Zahlungsinfo: ".trim($paypalInstructions):"\nDie konkreten PayPal-Zahlungsinformationen erhältst du separat nach der Anmeldung.";
 }else{
  $paymentText='Teilnahmebeitrag: '.workshopFeeLabel($fee).' · zahlbar vor Ort.';
 }

 if($kind==='payment'){
  if($isMember || $fee<=0) throw new InvalidArgumentException('Für diese Anmeldung ist keine Zahlung erforderlich.');
  if(trim($paypalInstructions)==='') throw new InvalidArgumentException('Die PayPal-Zahlungsinformation ist im Backoffice noch nicht hinterlegt.');
  $subject='💳 PayPal-Info: '.$workshop['title'];
  $text="Hallo ".$name.",\n\nfür deine Anmeldung zu ".$event." hast du PayPal gewählt.\n\nTeilnahmebeitrag: ".workshopFeeLabel($fee)."\nPayPal-Zahlungsinfo: ".trim($paypalInstructions)."\n\nBitte gib bei der Zahlung deinen Namen und das Workshopdatum an.\n\nSportliche Grüße\nMichael";
 }elseif($kind==='reminder'){
  $subject='⏰ Erinnerung: '.$workshop['title'].' am 4. März';
  $text="Hallo ".$name.",\n\nkurze Erinnerung an deine Anmeldung:\n\n".$event."\n".$venue."\n\n".$paymentText."\n\nFalls du doch nicht teilnehmen kannst, antworte bitte kurz auf diese E-Mail.\n\nBis bald!\nMichael";
 }else{
  $subject='🎾 Anmeldung bestätigt: '.$workshop['title'];
  $text="Hallo ".$name.",\n\nvielen Dank für deine Anmeldung. Dein Platz für den Regelworkshop ist vorgemerkt.\n\n".$event."\n".$venue."\n\n".$paymentText."\n\nFalls du doch nicht teilnehmen kannst, antworte bitte kurz auf diese E-Mail.\n\nSportliche Grüße\nMichael";
 }

 $esc=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
 $paragraphs='';
 foreach(preg_split('/\R{2,}/u',trim($text))?:[] as $part){
  $paragraphs.='<p style="margin:0 0 18px;font:15px/1.7 Arial,Helvetica,sans-serif;color:#365447">'.nl2br($esc(trim($part)),false).'</p>';
 }
 $html='<!doctype html><html lang="de"><meta charset="utf-8"><body style="margin:0;background:#eef3e8;padding:28px 12px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center"><table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#fff;border-radius:16px;overflow:hidden"><tr><td style="background:#143b2c;padding:27px 34px"><img src="https://tennis-schiri.de/assets/branding/ts-logo-dunkel.png?v=20261107-3" width="240" alt="Tennis-Schiri" style="display:block;max-width:100%;height:auto"></td></tr><tr><td style="height:5px;background:#c8f16d"></td></tr><tr><td style="padding:34px">'.$paragraphs.'<p style="margin:26px 0 0;font:12px/1.6 Arial,Helvetica,sans-serif;color:#738579">Tennis-Schiri · Regelworkshop · <a href="https://tennis-schiri.de/leistungen/#'.$esc($workshop['id']).'" style="color:#2a6844">Workshopseite</a></p></td></tr></table></td></tr></table></body></html>';
 return [$subject,$text,$html];
}

/** @return array{0:string,1:string,2:string} */
function workshopAdminNotification(array $workshop,array $registration): array {
 $subject='📝 Neue Workshop-Anmeldung: '.$workshop['title'];
 $text="Neue Anmeldung zum Regelworkshop.\n\n".
  "Name: ".($registration['name']??'')."\n".
  "E-Mail: ".($registration['email']??'')."\n".
  "Verein: ".(($registration['club']??'')?:'–')."\n".
  "NTC-93-Mitglied: ".(((int)($registration['is_member']??0)===1)?'ja':'nein')."\n".
  "Teilnahmebeitrag: ".workshopFeeLabel((int)($registration['fee_cents']??0))."\n".
  "Zahlungsart: ".workshopPaymentMethodLabel((string)($registration['payment_method']??''))."\n\n".
  "Termin: ".$workshop['date']." · ".$workshop['time']."\n".
  "Backend: https://tennis-schiri.de/admin/?view=workshops&workshop=".rawurlencode((string)$workshop['id']);
 $esc=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
 $html='<!doctype html><html lang="de"><meta charset="utf-8"><body style="font:15px/1.7 Arial,sans-serif;color:#193d2d"><h2>'.$esc($subject).'</h2><p>'.nl2br($esc($text),false).'</p></body></html>';
 return [$subject,$text,$html];
}

function workshopSendMail(string $to,string $subject,string $text,string $html): bool {
 if(!workshopSafeEmail($to)||$subject===''||strlen($subject)>240||preg_match('/[\r\n]/',$subject))return false;
 $boundary='ws_'.bin2hex(random_bytes(14));
 $headers=[
  'From: Tennis-Schiri Regelworkshops <turniere@tennis-schiri.de>',
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
