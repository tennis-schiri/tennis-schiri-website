<?php
declare(strict_types=1);
define('TS_WORKSHOP',true);
require dirname(__DIR__).'/public/workshop-lib.php';
function wsAssert(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"FEHLER: ".$message."\n");exit(1);}}
$catalog=workshopCatalog();wsAssert(isset($catalog['regelworkshop-2027-03-04']),'Workshop fehlt im Katalog');
$workshop=$catalog['regelworkshop-2027-03-04'];wsAssert((int)$workshop['guest_fee_cents']===1500,'Gastbeitrag muss 15 Euro betragen');
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);workshopSchema($db);workshopSchema($db);
workshopSetSetting($db,'paypal_instructions','paypal.me/beispiel',1);wsAssert(workshopSetting($db,'paypal_instructions')==='paypal.me/beispiel','PayPal-Einstellung wird nicht gespeichert');
$db->prepare("INSERT INTO workshop_registrations(workshop_id,name,email,club,is_member,fee_cents,payment_method,payment_status,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)")->execute([$workshop['id'],'<Alex & Co>','alex@example.com','TC Beispiel',0,1500,'paypal','open','registered',1,1]);
$row=$db->query('SELECT * FROM workshop_registrations')->fetch();wsAssert(is_array($row),'Workshop-Anmeldung nicht gespeichert');wsAssert(workshopFeeLabel(1500)==='15,00 €','Gebührenformat fehlerhaft');
$row['cancel_url']=workshopCancelUrl($row,'test-secret');
wsAssert(str_contains($row['cancel_url'],'action=cancel'),'Abmeldelink fehlt');
wsAssert(hash_equals(workshopCancelToken($row,'test-secret'),workshopCancelToken($row,'test-secret')),'Abmeldetoken nicht stabil');
wsAssert(!hash_equals(workshopCancelToken($row,'test-secret'),workshopCancelToken($row,'wrong-secret')),'Abmeldetoken ungeschützt');
wsAssert(str_contains(workshopPaypalLink(1500,$workshop['id']),'paypal%40tennis-schiri.de'),'PayPal-Empfänger fehlt');
wsAssert(str_contains(workshopPaypalLink(1500,$workshop['id']),'amount=15.00'),'PayPal-Betrag fehlt');
[$subject,$text,$html]=workshopMailContent($workshop,$row,'confirmation',workshopSetting($db,'paypal_instructions'));wsAssert(str_contains($subject,'Anmeldung bestätigt'),'Bestätigungsbetreff fehlt');wsAssert(str_contains($text,'15,00 €')&&str_contains($text,'paypal.me/beispiel'),'PayPal-Info fehlt');wsAssert(str_contains($html,'&lt;Alex &amp; Co&gt;'),'HTML-Anrede nicht escaped');wsAssert(str_contains($html,'Vom Regelworkshop abmelden'),'Abmeldebutton fehlt');wsAssert(str_contains($html,'15 € mit PayPal bezahlen'),'PayPal-Button fehlt');
[, $reminderText]=workshopMailContent($workshop,$row,'reminder','paypal.me/beispiel');wsAssert(str_contains($reminderText,'kurze Erinnerung'),'Erinnerungsmail fehlt');
[, $paymentText]=workshopMailContent($workshop,$row,'payment','paypal.me/beispiel');wsAssert(str_contains($paymentText,'Workshopdatum'),'PayPal-Mail unvollständig');
[, $cancelText]=workshopMailContent($workshop,$row,'cancelled');
wsAssert(str_contains($cancelText,'Abmeldung'),'Abmeldebestätigung fehlt');
$member=$row;$member['is_member']=1;$member['fee_cents']=0;$member['payment_method']='free';[, $memberText]=workshopMailContent($workshop,$member,'confirmation','');wsAssert(str_contains($memberText,'kostenfrei'),'Mitgliedsvorteil fehlt');
try{workshopMailContent($workshop,$member,'payment','paypal.me/beispiel');wsAssert(false,'Zahlungsmail für kostenfreies Mitglied zugelassen');}catch(InvalidArgumentException $expected){}
[$adminSubject,$adminText]=workshopAdminNotification($workshop,$row);wsAssert(str_contains($adminSubject,'Neue Workshop-Anmeldung')&&str_contains($adminText,'alex@example.com'),'Admin-Benachrichtigung fehlt');
[$cancelAdminSubject,$cancelAdminText]=workshopAdminCancellationNotification($workshop,$row);
wsAssert(str_contains($cancelAdminText,'alex@example.com')&&str_contains($cancelAdminSubject,'Abmeldung'),'Abmeldeinformation an Admin fehlt');
fwrite(STDOUT,"Regelworkshop: Anmeldung, Gebühren, Einstellungen und Mailvorlagen geprüft.\n");
