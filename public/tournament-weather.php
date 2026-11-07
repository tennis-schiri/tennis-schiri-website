<?php
declare(strict_types=1);
/* Public, read-only weather and astronomical sunset for known outdoor tennis events.
   Forecast is fetched only within 7 days of play; it is never generated or guessed. */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=900');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: https://tennis-schiri.de');
function weatherReply(array $payload,int $code=200): never {
 http_response_code($code);
 echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
 exit;
}
function weatherData(string $id,?DateTimeImmutable $now=null): array {
 $now=$now??new DateTimeImmutable('now',new DateTimeZone('Europe/Berlin'));
 $catalog=__DIR__.'/data/newsletter-events.json';
 if(!is_file($catalog))return ['available'=>false,'reason'=>'Katalog nicht bereit'];
 $json=json_decode((string)file_get_contents($catalog),true);
 if(!is_array($json)||!is_array($json['events']??null))return ['available'=>false];
 $event=null;
 foreach($json['events'] as $item)if(is_array($item)&&($item['id']??'')===$id&&($item['type']??'')==='turnier'){$event=$item;break;}
 if(!$event)return ['available'=>false,'reason'=>'Turnier unbekannt'];
 if(($event['outdoor']??false)!==true)return ['available'=>false,'reason'=>'Hallenturnier'];
 $place=$event['weather_region']??null;
 if(!is_array($place)||!is_numeric($place['lat']??null)||!is_numeric($place['lon']??null)){
  return ['available'=>false,'reason'=>'Wetterstandort noch nicht hinterlegt'];
 }
 $tz=new DateTimeZone('Europe/Berlin');
 $today=new DateTimeImmutable($now->format('Y-m-d').' 12:00:00',$tz);
 $from=(string)($event['date_start']??'');
 $to=(string)($event['date_end']??$from);
 if(!preg_match('/^20\d\d-\d\d-\d\d$/D',$from)||!preg_match('/^20\d\d-\d\d-\d\d$/D',$to))return ['available'=>false];
 try{$start=new DateTimeImmutable($from.' 12:00:00',$tz);$end=new DateTimeImmutable($to.' 12:00:00',$tz);}
 catch(Throwable $ex){return ['available'=>false];}
 if($end<$today)return ['available'=>false,'reason'=>'Turnier abgeschlossen'];
 $target=$start>$today?$start:$today; // on multi-day events, show the current day
 $days=(int)$today->diff($target)->format('%r%a');
 $latitude=(float)$place['lat'];$longitude=(float)$place['lon'];
 if($latitude<45||$latitude>56||$longitude<5||$longitude>16)return ['available'=>false];
 $timestamp=date_sunset($target->getTimestamp(),SUNFUNCS_RET_TIMESTAMP,$latitude,$longitude);
 $sunset=$timestamp!==false?(new DateTimeImmutable('@'.(int)$timestamp))->setTimezone($tz)->format('H:i'):null;
 $response=[
  'available'=>true,'id'=>$id,'outdoor'=>true,'date'=>$target->format('Y-m-d'),
  'location'=>(string)($place['label']??$event['location']),'sunset'=>$sunset,
  'forecast'=>null,'forecast_notice'=>$days>7?'Wettervorhersage ab 7 Tage vor dem Turnier verfügbar.':null,
  'source'=>'Open-Meteo','source_url'=>'https://open-meteo.com/en/docs',
 ];
 if($days>7)return $response; // no model prediction beyond requested window
 $query=http_build_query([
  'latitude'=>$latitude,'longitude'=>$longitude,'timezone'=>'Europe/Berlin',
  'forecast_days'=>8,'daily'=>'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,wind_speed_10m_max,sunset'
 ],'','&',PHP_QUERY_RFC3986);
 $key=hash('sha256',$latitude.':'.$longitude.':'.$today->format('Y-m-d'));
 $file=sys_get_temp_dir().'/ts-weather-'.$key.'.json';
 $data=null;
 if(is_file($file)&&time()-(int)filemtime($file)<10800)$data=json_decode((string)file_get_contents($file),true);
 if(!is_array($data)){
  $url='https://api.open-meteo.com/v1/forecast?'.$query;
  $raw=false;
  if(function_exists('curl_init')){
   $ch=curl_init($url);
   curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>4,CURLOPT_CONNECTTIMEOUT=>2,
       CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_USERAGENT=>'TennisSchiri-Website/1.0']);
   $raw=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
   if($http!==200)$raw=false;
  }elseif(filter_var(ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOLEAN)){
   $context=stream_context_create(['http'=>['timeout'=>4,'follow_location'=>0,'user_agent'=>'TennisSchiri-Website/1.0']]);
   $raw=@file_get_contents($url,false,$context);
  }
  if(is_string($raw)&&strlen($raw)<150000)$data=json_decode($raw,true);
  if(is_array($data)&&is_array($data['daily']['time']??null)){
   @file_put_contents($file,json_encode($data),LOCK_EX);
  }
 }
 $daily=is_array($data)?($data['daily']??[]):[];
 $times=$daily['time']??[];
 $idx=is_array($times)?array_search($target->format('Y-m-d'),$times,true):false;
 if($idx===false)return $response;
 $val=static fn(string $field)=>is_array($daily[$field]??null)?($daily[$field][$idx]??null):null;
 $code=$val('weather_code');
 $max=$val('temperature_2m_max');$min=$val('temperature_2m_min');
 $chance=$val('precipitation_probability_max');$wind=$val('wind_speed_10m_max');
 if(!is_numeric($code)||!is_numeric($max)||!is_numeric($min))return $response;
 $response['forecast']=[
   'description'=>weatherCode((int)$code),
   'temperature_max'=>(int)round((float)$max),
   'temperature_min'=>(int)round((float)$min),
   'precipitation_probability'=>is_numeric($chance)?(int)round((float)$chance):null,
   'wind_max'=>is_numeric($wind)?(int)round((float)$wind):null,
   'retrieved_at'=>gmdate('Y-m-d\TH:i:s\Z'),
 ];
 $sun=$val('sunset');
 if(is_string($sun)&&preg_match('/T(\d{2}:\d{2})/',$sun,$matches))$response['sunset']=$matches[1];
 $response['forecast_notice']='Unverbindliche Wetterprognose; kurzfristige Änderungen möglich.';
 return $response;
}
function weatherCode(int $code): string {
 return match(true){
   $code===0=>'Sonnig',$code>=1&&$code<=3=>'Teilweise bewölkt',
   $code===45||$code===48=>'Nebel',
   $code>=51&&$code<=57=>'Nieselregen',
   $code>=61&&$code<=67=>'Regen',
   $code>=71&&$code<=77=>'Schnee',
   $code>=80&&$code<=82=>'Regenschauer',
   $code>=85&&$code<=86=>'Schneeschauer',
   $code>=95=>'Gewitter',
   default=>'Wechselhaft'
 };
}
if(!defined('TS_WEATHER_LIBRARY')){
 $id=$_GET['id']??'';
 if(!is_string($id)||!preg_match('/^turnier-[a-z0-9-]{4,110}$/D',$id))weatherReply(['available'=>false],400);
 weatherReply(weatherData($id));
}
