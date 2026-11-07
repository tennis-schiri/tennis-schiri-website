<?php
declare(strict_types=1);
define('TS_NEWSLETTER_TEMPLATE',true);
require dirname(__DIR__).'/public/newsletter-template.php';

function ensure(bool $condition,string $why): void {
 if(!$condition){fwrite(STDERR,"FAILED: ".$why."\n");exit(1);}
}
$campaign=[
 'subject'=>'Aktuelle Turniere im November',
 'topic'=>'turniere',
 'headline'=>'Neue LK-Turniere & freie Plätze',
 'preheader'=>'Melde dich jetzt für neue Turniere an.',
 'body'=>"Am Samstag spielen wir Tennis.\n\nDie Anmeldung ist geöffnet.\nWeitere Details folgen.",
 'button_label'=>'Zur Turnierübersicht',
 'button_url'=>'https://tennis-schiri.de/turniere/',
 'event_title'=>'Hallen-LK-Turnier Bad Saarow',
 'event_date'=>'21.11.2026 · ab 10 Uhr',
 'event_location'=>'Bad Saarow · Tennishalle'
];
$url='https://tennis-schiri.de/newsletter.php?action=unsubscribe-signed&id=123&sig='.str_repeat('a',64);
$html=newsletterTemplate($campaign,$url);
$text=newsletterPlainText($campaign,$url);
ensure(str_contains($html,'<html lang="de">'),'HTML-Root fehlt');
ensure(str_contains($html,'Neue LK-Turniere &amp; freie Plätze'),'HTML-Überschrift wurde nicht escaped');
ensure(str_contains($html,'Zur Turnierübersicht'),'CTA nicht dargestellt');
ensure(str_contains($html,'Hallen-LK-Turnier Bad Saarow'),'Eventkarte fehlt');
ensure(str_contains($html,'Bad Saarow · Tennishalle'),'Ort auf Eventkarte fehlt');
ensure(str_contains($text,'Termin: 21.11.2026'),'Textalternative ohne Termin');
ensure(str_contains(newsletterTemplate($campaign,$url,true),'TEST / VORSCHAU'),'Vorschaubanner fehlt');
ensure(!str_contains($html,'TEST / VORSCHAU'),'Echte Nachricht enthält Testbanner');
ensure(str_contains($html,'href="https://tennis-schiri.de/turniere/"'),'CTA nicht erreichbar');
ensure(str_contains($html,'id=123&amp;sig='),'Abmeldelink fehlt oder ungeescaped');
ensure(str_contains($html,'text-align:center'),'Tabellenlayout nicht geladen');
ensure(str_contains($text,'Abmelden: '.$url),'Textvariante ohne persönlichen Abmeldelink');
ensure(str_contains($text,'Zur Turnierübersicht: https://tennis-schiri.de/turniere/'),'Textvariante ohne CTA');
ensure(str_contains($html,'src="https://tennis-schiri.de/assets/branding/ts-logo-dunkel.png"'),'Freigegebenes TS-09c-Logo im E-Mail-Kopf fehlt');
ensure(substr_count($html,'<img ')===1,'Bei einer Ausgabe ohne Partner nur das TS-Logo ausgeben – keine Trackingpixel');
$injection=$campaign;
$injection['headline']='<script>alert("x")</script>';
$injection['body']="<img src=x onerror=alert(1)>";
$injection['button_label']='"><svg onload=alert(1)>';
$injection['button_url']='javascript:alert(1)';
$escaped=newsletterTemplate($injection,$url);
ensure(!str_contains($escaped,'<script>')&&!str_contains($escaped,'<svg')&&!str_contains($escaped,'onerror=alert(1)>'),'HTML-Eingaben unsicher');
ensure(!str_contains($escaped,'javascript:alert(1)'),'Unsicheres CTA-Protokoll wurde ausgegeben');
$old=['subject'=>'Älterer Entwurf','body'=>'Ein bestehender Newsletter ohne zusätzliche Felder.','topic'=>'regeln'];
ensure(str_contains(newsletterTemplate($old,$url),'Älterer Entwurf'),'Alt-Kampagne nicht kompatibel');
ensure(str_contains(newsletterPlainText($old,$url),'Ein bestehender Newsletter'),'Alt-Kampagne ohne Textalternative');
fwrite(STDOUT,"Newsletter-HTML-Vorlage und Textalternative geprüft.\n");
