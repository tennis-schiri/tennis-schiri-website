<?php
declare(strict_types=1);
if(!defined('TS_BACKOFFICE')){http_response_code(404);exit;}
define('TS_WEATHER_LIBRARY',true);
require_once dirname(__DIR__).'/tournament-weather.php';
function boWeatherMailBlocks(array $campaign):array {
 if((int)($campaign['weather_enabled']??1)!==1)return ['',''];
 $id=(string)($campaign['tournament_id']??'');
 if(!preg_match('/^turnier-[a-z0-9-]{4,110}$/D',$id))return ['',''];
 $weather=weatherData($id);
 if(($weather['available']??false)!==true)return ['',''];
 $escape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
 $loc=$escape($weather['location']??'');
 $date=$escape((new DateTimeImmutable($weather['date'].' 12:00:00',new DateTimeZone('Europe/Berlin')))->format('d.m.Y'));
 $sunset=$weather['sunset']??null;
 $forecast=$weather['forecast']??null;
 if($sunset===null && !is_array($forecast))return ['',''];
 $lines=[];
 $plain="\n\nOUTDOOR-WETTER UND TAGESLICHT (".$date.", ".$loc.")\n";
 if(is_array($forecast)){
  $summary=$escape($forecast['description']).' · '.$escape($forecast['temperature_min']).' bis '.$escape($forecast['temperature_max']).' °C';
  $lines[]=$summary;
  $plain.=$forecast['description'].', '.$forecast['temperature_min'].' bis '.$forecast['temperature_max']." °C\n";
  if($forecast['precipitation_probability']!==null){
    $rain=(int)$forecast['precipitation_probability'];
    $lines[]='Niederschlagswahrscheinlichkeit: '.$rain.' %';
    $plain.="Niederschlagswahrscheinlichkeit: ".$rain." %\n";
  }
  if($forecast['wind_max']!==null){
    $wind=(int)$forecast['wind_max'];
    $lines[]='Max. Wind: '.$wind.' km/h';
    $plain.="Max. Wind: ".$wind." km/h\n";
  }
 }else{
  $lines[]='Die Wetterprognose ist etwa sieben Tage vor dem Turnier verfügbar.';
  $plain.="Wettervorhersage: ab etwa sieben Tage vor dem Turnier verfügbar.\n";
 }
 if($sunset!==null){
  $lines[]='🌇 Sonnenuntergang: '.$escape($sunset).' Uhr';
  $plain.='Sonnenuntergang: '.$sunset." Uhr\n";
 }
 $html='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;background:#edf7e9;border:1px solid #d4e6d0"><tr><td style="padding:18px 20px;font-family:Arial,sans-serif"><p style="margin:0 0 9px;font-weight:800;letter-spacing:1px;color:#2e6543;font-size:11px">☀️ WETTER &amp; TAGESLICHT · '.$date.'</p>';
 foreach($lines as $line)$html.='<p style="margin:5px 0;color:#244934;font-size:14px;line-height:1.6">'.$line.'</p>';
 $html.='<p style="margin:10px 0 0;color:#738778;font-size:11px">Unverbindliche Prognose für '.$loc.' (Umgebung, nicht direkt am Platz). Quelle: <a href="https://brightsky.dev/" style="color:#27664b">DWD / Bright Sky</a>. Änderungen möglich.</p></td></tr></table>';
 $plain.="Unverbindliche Vorhersage für die Umgebung. Quelle: https://brightsky.dev/\n";
 return [$html,$plain];
}
