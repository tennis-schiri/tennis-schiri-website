<?php
declare(strict_types=1);
define('TS_NEWSLETTER_EVENTS',true);
define('TS_NEWSLETTER_TEMPLATE',true);
require dirname(__DIR__).'/public/newsletter-events.php';
require dirname(__DIR__).'/public/newsletter-template.php';

function assertEvent(bool $ok,string $message): void {
 if(!$ok){fwrite(STDERR,"FEHLER: ".$message."\n");exit(1);}
}
$catalog=newsletterEventsCatalog();
assertEvent(count($catalog)>=10,'Automatisch erstellte Turnier-/Eventliste fehlt');
assertEvent(count(array_filter($catalog,fn($e)=>$e['type']==='turnier'))>=9,'Turniere fehlen');
assertEvent(count(array_filter($catalog,fn($e)=>$e['type']==='veranstaltung'))>=1,'Workshops fehlen');
$ids=array_column($catalog,'id');
assertEvent(count($ids)===count(array_unique($ids)),'Doppelte IDs');
$first=$catalog[0];
assertEvent(str_starts_with($first['url'],'https://'),'Unsicherer Link');
assertEvent($first['title']!=='' && $first['date']!=='' && $first['location']!=='','Leere Datenfelder');
assertEvent(str_contains($first['url'],'tennis.de') || str_contains($first['url'],'tennis-schiri.de'),'Unerwartete Linkquelle');
$selection=newsletterResolveSelection([$first['id']]);
assertEvent(count($selection)===1 && $selection[0]['id']===$first['id'],'Checkbox-Auswahl nicht aufgelöst');
assertEvent(newsletterResolveSelection(['ungültige-id-der-nicht-existiert'])===[],'Unbekannte ID akzeptiert');
$chosen=json_encode($selection,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
$fields=newsletterEventSelector($chosen);
assertEvent(str_contains($fields,'name="selected_events[]"'),'Auswahlfelder fehlen');
assertEvent(str_contains($fields,'checked'),'Entwurf verliert Auswahl');
$campaign=[
 'subject'=>'Turniernewsletter Test',
 'topic'=>'turniere',
 'body'=>'Unsere nächsten Termine für dich.',
 'selected_events'=>$chosen
];
$html=newsletterTemplate($campaign,'https://tennis-schiri.de/newsletter.php?action=unsubscribe-request');
$text=newsletterPlainText($campaign,'https://tennis-schiri.de/newsletter.php?action=unsubscribe-request');
assertEvent(str_contains($html,'TERMINE AUF EINEN BLICK'),'Newsletter ohne Event-Karten');
assertEvent(str_contains($html,htmlspecialchars($first['title'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')),'Turniername fehlt im HTML');
assertEvent(str_contains($html,htmlspecialchars($first['url'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')),'Terminkarte nicht verlinkt');
assertEvent(str_contains($text,$first['url']),'Textalternative ohne Direktlink');
assertEvent(str_contains($text,$first['title']),'Textalternative ohne Event');
$noEvents=$campaign;
$noEvents['selected_events']='[]';
assertEvent(!str_contains(newsletterTemplate($noEvents,'https://tennis-schiri.de/'),'TERMINE AUF EINEN BLICK'),'Leere Auswahlliste gerendert');
fwrite(STDOUT,"Newsletter-Terminimport und verlinkte Karten geprüft: ".count($catalog)." Termine.\n");
