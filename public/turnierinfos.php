<?php
declare(strict_types=1);
/* Public tournament-specific, editor-approved facts.
 * Never render a campaign's personalized body, delivery logs or contact data. */
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: public, max-age=600');
$cfgFile=dirname(__DIR__).'/tennis-schiri-newsletter-config.php';
$db=null;
if(is_file($cfgFile)){
 $cfg=require $cfgFile;
 if(is_array($cfg)&&!empty($cfg['database'])&&is_file((string)$cfg['database'])){
  try{$db=new PDO('sqlite:'.$cfg['database'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
  catch(Throwable $e){$db=null;}
 }
}
function pubEscape(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function pubEntries(?PDO $db):array{
 if(!$db)return [];
 try{
  $r=$db->query('SELECT tournament_id FROM bo_public_infos ORDER BY published_at DESC LIMIT 500');
  return $r?$r->fetchAll(PDO::FETCH_COLUMN):[];
 }catch(Throwable $e){return [];}
}
if(isset($_GET['status'])){
 header('Content-Type: application/json; charset=UTF-8');
 echo json_encode(['published'=>pubEntries($db)],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
 exit;
}
$id=$_GET['turnier']??'';
if(!is_string($id)||!preg_match('/^turnier-[a-z0-9-]{4,110}$/D',$id)){
 http_response_code(404);exit('Turnier nicht gefunden.');
}
$row=null;
if($db){
 try{$q=$db->prepare('SELECT payload,published_at FROM bo_public_infos WHERE tournament_id=?');$q->execute([$id]);$row=$q->fetch();}
 catch(Throwable $e){$row=null;}
}
if(!$row){http_response_code(404);exit('Für dieses Turnier sind noch keine öffentlichen Informationen freigegeben.');}
$pub=json_decode((string)$row['payload'],true);
if(!is_array($pub)||($pub['version']??0)!==1){http_response_code(404);exit('Turnierinformationen nicht verfügbar.');}
$title=pubEscape($pub['title']??'Turnierinfos');$place=pubEscape($pub['location']??'');
$date=pubEscape($pub['date']??'');$url=(string)($pub['url']??'');
if(!filter_var($url,FILTER_VALIDATE_URL)||parse_url($url,PHP_URL_SCHEME)!=='https')$url='';
$stamp=gmdate('d.m.Y H:i',(int)$row['published_at']);
echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#142b29"><title>'.$title.' · Turnierinfos · Tennis-Schiri</title><meta name="description" content="Organisatorische Hinweise, Ablauf und wichtige Informationen zum Tennisturnier."><link rel="icon" type="image/png" href="/assets/branding/ts-favicon.png"><link rel="stylesheet" href="/assets/site.css?v=20261107-4"><script src="/assets/tournament-calendar.js?v=1" defer></script></head><body>';
echo '<header class="site-head"><div class="container header-inner"><a class="brand brand-logo" href="/" aria-label="Tennis-Schiri Startseite"><img src="/assets/branding/ts-logo-dunkel.png?v=20261107-3" alt="Tennis-Schiri" width="250" style="height:auto"></a><nav class="nav"><a href="/turniere/">Turniere</a><a href="/">Startseite</a></nav></div></header>';
echo '<main><section class="interior-hero"><div class="container"><div class="breadcrumb"><a href="/">Startseite</a> › <a href="/turniere/">Turniere</a> › Informationen</div><div class="eyebrow" style="margin-top:28px">FÜR DEINEN TURNIERTAG</div><h1>'.$title.'</h1><p>'.$date.' · '.$place.'</p></div></section>';
echo '<section class="inner-section"><div class="container" style="max-width:960px"><p class="info-note">Diese öffentlichen Hinweise wurden nach dem Versand einer Teilnehmer-Infomail ausdrücklich freigegeben. Verbindlich bleiben die aktuelle Ausschreibung und die Hinweise der Turnierleitung. Zuletzt veröffentlicht: '.pubEscape($stamp).' Uhr.</p>';
echo '<div class="public-info-grid">';
foreach(array_slice((array)($pub['sections']??[]),0,50) as $item){
 if(!is_array($item))continue;
 $label=pubEscape($item['label']??'');$value=(string)($item['value']??'');
 if($label===''||$value==='')continue;
 $body=nl2br(pubEscape($value),false);
 if(($item['type']??'')==='url'){
  if(!filter_var($value,FILTER_VALIDATE_URL)||parse_url($value,PHP_URL_SCHEME)!=='https')continue;
  $body='<a href="'.pubEscape($value).'" target="_blank" rel="noopener noreferrer">Informationen öffnen ↗</a>';
 }
 echo '<article class="public-info-card"><h2>'.$label.'</h2><p>'.$body.'</p></article>';
}
echo '</div>';
if($url!=='')echo '<p style="margin:25px 0"><a class="tournament-cta" href="'.pubEscape($url).'" target="_blank" rel="noopener noreferrer">Offizielle Turnierseite ↗</a></p>';
echo '<p><a href="/turniere/#'.pubEscape($id).'">← Zur Turnierübersicht</a></p></div></section></main></body></html>';
