<?php
declare(strict_types=1);
define('TS_WEATHER_LIBRARY',true);
require dirname(__DIR__).'/public/tournament-weather.php';
function checkWeather(bool $ok,string $msg):void{if(!$ok){fwrite(STDERR,"Outdoor-Test FEHLER: $msg\n");exit(1);}}
$catalog=json_decode((string)file_get_contents(dirname(__DIR__).'/public/data/newsletter-events.json'),true);
checkWeather(is_array($catalog),'Katalog nicht erstellt');
$items=(array)($catalog['events']??[]);
$summer=array_values(array_filter($items,static fn($e)=>($e['type']??'')==='turnier'&&($e['outdoor']??false)===true));
$indoor=array_values(array_filter($items,static fn($e)=>($e['type']??'')==='turnier'&&($e['outdoor']??true)===false));
checkWeather(count($summer)>=4,'Outdoor-Turniere nicht erkannt');
checkWeather(count($indoor)>=3,'Hallenturniere nicht erkannt');
checkWeather(count(array_filter($summer,static fn($e)=>isset($e['weather_region']['lat'])))===count($summer),'Wetterort nicht eingetragen');
checkWeather(count(array_filter($items,static fn($e)=>isset($e['date_end'])))===count($items),'Mehrtagesturniere ohne Enddatum');
$now=new DateTimeImmutable('2026-11-07 12:00:00',new DateTimeZone('Europe/Berlin'));
$a=weatherData($summer[0]['id'],$now);
checkWeather($a['available']===true,'Sonnenuntergang für künftiges Outdoor-Turnier fehlt');
checkWeather(is_string($a['sunset'])&&preg_match('/^\d\d:\d\d$/D',$a['sunset'])===1,'Sonnenuntergang hat falsches Format');
checkWeather($a['forecast']===null,'Wetter mehr als 7 Tage vorher darf nicht erfunden werden');
$b=weatherData($indoor[0]['id'],$now);
checkWeather(($b['available']??true)===false,'Indoor-Wetter wurde nicht ausgeschlossen');
$rows=[
 ['timestamp'=>'2027-04-24T08:00:00+02:00','temperature'=>11.1,'precipitation_probability'=>20,'wind_speed'=>12.5,'icon'=>'partly-cloudy-day'],
 ['timestamp'=>'2027-04-24T15:00:00+02:00','temperature'=>18.2,'precipitation_probability'=>40,'wind_speed'=>16.4,'icon'=>'partly-cloudy-day'],
 ['timestamp'=>'2027-04-25T08:00:00+02:00','temperature'=>6.0,'precipitation_probability'=>70,'wind_speed'=>10.0,'icon'=>'rain']
];
$c=weatherAggregateDwd($rows,'2027-04-24');
checkWeather($c!==null&&$c['temperature_max']===18&&$c['temperature_min']===11,'DWD Temperatur-Tageswerte falsch');
checkWeather($c['precipitation_probability']===40,'Niederschlagswahrscheinlichkeit falsch aggregiert');
checkWeather($c['wind_max']===16,'Windaggregation falsch');
checkWeather(weatherAggregateDwd([],'2027-04-24')===null,'Wetter ohne Daten erfunden');
fwrite(STDOUT,"Outdoor / Indoor, 7-Tage-Sperre, Tageslicht und DWD-Wetter-Aggregation erfolgreich.\n");
