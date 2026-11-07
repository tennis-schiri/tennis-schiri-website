<?php
declare(strict_types=1);

define('TS_NEWSLETTER_PARTNERS',true);
define('TS_NEWSLETTER_TEMPLATE',true);
require dirname(__DIR__).'/public/newsletter-partners.php';
require dirname(__DIR__).'/public/newsletter-template.php';

function assertPartners(bool $okay,string $detail):void{
 if(!$okay){fwrite(STDERR,"FEHLER: ".$detail."\n");exit(1);}
}
$catalog=newsletterPartnersCatalog();
assertPartners(count($catalog)===8,'Sieben Website-Partner und TENNILO müssen importiert sein');
assertPartners(count(array_filter($catalog,fn($p)=>$p['category']==='partner'))===7,'Website-Partneranzahl falsch');
assertPartners(count(array_filter($catalog,fn($p)=>$p['category']==='projekt'))===1,'TENNILO fehlt');
$ids=array_column($catalog,'id');
assertPartners(count(array_unique($ids))===count($ids),'Doppelte Partner-IDs');
assertPartners(in_array('tennilo',$ids,true),'TENNILO-ID fehlt');
$tennilo=$catalog[array_search('tennilo',$ids,true)];
assertPartners($tennilo['url']==='https://tennilo.de/','TENNILO-Link falsch');
assertPartners($tennilo['logo']==='https://tennilo.de/assets/tennilo-logo.png','TENNILO-Original-Logo fehlt');
foreach($catalog as $p){
 assertPartners(newsletterPartnerUrl($p['url']),'Ungültiger Link '.$p['name']);
 assertPartners(newsletterPartnerLogo($p['logo']),'Ungültiges Logo '.$p['name']);
 assertPartners($p['name']!=='','Partnername leer');
}
assertPartners(!newsletterPartnerLogo('https://evil.example/logo.png'),'Fremd-Logo zugelassen');
assertPartners(!newsletterPartnerUrl('javascript:alert(1)'),'Unsicherer Link zugelassen');
assertPartners(!newsletterPartnerUrl('https://example.com/'."\r\n".'Bcc: victim@example.com'),'Mailheader-Injektion möglich');
assertPartners(newsletterResolvePartners(['nicht-vorhanden'])===[],'Unbekannte ID zugelassen');

$chosen=newsletterResolvePartners([$catalog[0]['id'],'tennilo']);
assertPartners(count($chosen)===2,'Auswahl nicht korrekt aufgelöst');
$serialized=json_encode($chosen,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
$selector=newsletterPartnersSelector($serialized);
assertPartners(str_contains($selector,'name="selected_partners[]"'),'Checkboxen fehlen');
assertPartners(substr_count($selector,' checked')===2,'Auswahl im Entwurf nicht vorbelegt');
assertPartners(str_contains($selector,'TENNILO'),'TENNILO nicht im Admin sichtbar');
$campaign=['subject'=>'Partnerübersicht','topic'=>'turniere','body'=>'Aktuelles aus dem Tennis.','selected_partners'=>$serialized];
$html=newsletterTemplate($campaign,'https://tennis-schiri.de/newsletter.php?action=unsubscribe-request');
$text=newsletterPlainText($campaign,'https://tennis-schiri.de/newsletter.php?action=unsubscribe-request');
assertPartners(str_contains($html,'PARTNER &amp; PROJEKTE'),'Partner-Bereich im HTML fehlt');
assertPartners(str_contains($html,'<img src='),'Partnerlogos nicht sichtbar');
assertPartners(str_contains($html,htmlspecialchars($catalog[0]['url'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')),'Partnerlink fehlt');
assertPartners(str_contains($html,'https://tennilo.de/assets/tennilo-logo.png'),'TENNILO-Logo fehlt im Newsletter');
assertPartners(str_contains($html,'href="https://tennilo.de/"'),'TENNILO nicht direkt verlinkt');
assertPartners(str_contains($text,'PARTNER & PROJEKTE'),'Partner-Textalternative fehlt');
assertPartners(str_contains($text,'TENNILO: https://tennilo.de/'),'TENNILO-Textlink fehlt');

$empty=$campaign;
$empty['selected_partners']='[]';
assertPartners(!str_contains(newsletterTemplate($empty,'https://tennis-schiri.de/'),'PARTNER &amp; PROJEKTE'),'Banner erscheinen ohne Auswahl');
$attack=['id'=>'evil','category'=>'partner','name'=>'<script>alert(1)</script>',
 'url'=>'https://example.com/','logo'=>'https://not-safe.example/logo.png','caption'=>'<svg onload=alert(1)>'];
$attack['url']='javascript:alert(1)';
$campaign['selected_partners']=json_encode([$attack],JSON_THROW_ON_ERROR);
$rendered=newsletterTemplate($campaign,'https://tennis-schiri.de/');
assertPartners(!str_contains($rendered,'<script>') && !str_contains($rendered,'<svg'),'Partnertexte nicht escaped');
assertPartners(!str_contains($rendered,'javascript:alert(1)'),'Unsicherer Partnerlink gerendert');
assertPartners(!str_contains($rendered,'https://not-safe.example/logo.png'),'Ungenehmigtes Logo gerendert');

fwrite(STDOUT,"Newsletter-Partnerbanner getestet: 7 Partner + TENNILO, Auswahl und Links sicher.\n");
