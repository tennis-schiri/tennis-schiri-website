<?php
declare(strict_types=1);
/* Public, read-only weather and astronomical sunset for known outdoor tennis events.
   DWD forecast via Bright Sky is fetched only within 7 days of play; never guessed. */
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
  'source'=>'DWD / Bright Sky','source_url'=>'https://brightsky.dev/',
 ];
 if($days>7)return $response; // no model prediction beyond requested window
 $query=http_build_query([
  'lat'=>$latitude,'lon'=>$longitude,'date'=>$target->format('Y-m-d'),
  'last_date'=>$target->modify('+1 day')->format('Y-m-d'),
  'tz'=>'Europe/Berlin','units'=>'dwd',
 ],'','&',PHP_QUERY_RFC3986);
 $key=hash('sha256',$latitude.':'.$longitude.':'.$target->format('Y-m-d'));
 $file=sys_get_temp_dir().'/ts-dwd-weather-'.$key.'.json';
 $data=null;
 if(is_file($file)&&time()-(int)filemtime($file)<10800)$data=json_decode((string)@file_get_contents($file),true);
 if(!is_array($data)){
  $url='https://api.brightsky.dev/weather?'.$query;
  $raw=false;
  if(function_exists('curl_init')){
   $ch=curl_init($url);
   curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>4,CURLOPT_CONNECTTIMEOUT=>2,
    CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_USERAGENT=>'TennisSchiri-Website/1.0']);
   $raw=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
   if($http!==200)$raw=false;
  }elseif(filter_var(ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOLEAN)){
   $ctx=stream_context_create(['http'=>['timeout'=>4,'follow_location'=>0,'user_agent'=>'TennisSchiri-Website/1.0']]);
   $raw=@file_get_contents($url,false,$ctx);
  }
  if(is_string($raw)&&strlen($raw)<350000)$data=json_decode($raw,true);
  if(is_array($data)&&is_array($data['weather']??null))
    @file_put_contents($file,json_encode($data,JSON_INVALID_UTF8_SUBSTITUTE),LOCK_EX);
 }
 $forecast=is_array($data)?weatherAggregateDwd((array)($data['weather']??[]),$target->format('Y-m-d')):null;
 if($forecast===null)return $response;
 $response['forecast']=$forecast;
 $response['forecast_notice']='Unverbindliche DWD-Wetterprognose; kurzfristige Änderungen möglich.';
 return $response;
}
/** Aggregate hourly Bright Sky DWD forecasts for one Europe/Berlin calendar day. */
function weatherAggregateDwd(array $rows,string $target): ?array {
 $temp=[];$rain=[];$wind=[];$icons=[];
 $tz=new DateTimeZone('Europe/Berlin');
 foreach($rows as $row){
  if(!is_array($row)||empty($row['timestamp']))continue;
  try{$day=(new DateTimeImmutable((string)$row['timestamp']))->setTimezone($tz)->format('Y-m-d');}
  catch(Throwable $e){continue;}
  if($day!==$target)continue;
  if(is_numeric($row['temperature']??null))$temp[]=(float)$row['temperature'];
  if(is_numeric($row['precipitation_probability']??null))$rain[]=(int)$row['precipitation_probability'];
  if(is_numeric($row['wind_speed']??null))$wind[]=(float)$row['wind_speed'];
  if(is_string($row['icon']??null))$icons[$row['icon']]=($icons[$row['icon']]??0)+1;
 }
 if(!$temp)return null;  // never invent a forecast
 arsort($icons);
 $icon=array_key_first($icons);
 $word=match($icon){
  'clear-day','clear-night'=>'Sonnig / klar',
  'partly-cloudy-day','partly-cloudy-night'=>'Teils bewölkt',
  'cloudy'=>'Bewölkt',
  'fog'=>'Nebel',
  'wind'=>'Windig',
  'rain','sleet'=>'Regen / Schauer',
  'snow'=>'Schnee',
  'hail'=>'Hagel',
  'thunderstorm'=>'Gewitter',
  default=>'Wechselhaft'
 };
 return [
  'description'=>$word,
  'temperature_max'=>(int)round(max($temp)),
  'temperature_min'=>(int)round(min($temp)),
  'precipitation_probability'=>$rain?max($rain):null,
  'wind_max'=>$wind?(int)round(max($wind)):null,
  'retrieved_at'=>gmdate('Y-m-d\TH:i:s\Z'),
 ];
}
}
if(!defined('TS_WEATHER_LIBRARY')){
 $id=$_GET['id']??'';
 if(!is_string($id)||!preg_match('/^turnier-[a-z0-9-]{4,110}$/D',$id))weatherReply(['available'=>false],400);
 weatherReply(weatherData($id));
}
