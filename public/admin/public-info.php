<?php
declare(strict_types=1);
if(!defined('TS_BACKOFFICE')){http_response_code(404);exit;}

/* Public summaries are opt-in snapshots, not copies of personalized emails.
   Recipient names, participant addresses, greeting, notes and organizer-only
   contact fields are never added to the public payload. */
function boPublicInfoSchema(PDO $db):void {
 $db->exec("CREATE TABLE IF NOT EXISTS bo_public_infos (
   tournament_id TEXT PRIMARY KEY,
   campaign_id INTEGER NOT NULL,
   payload TEXT NOT NULL,
   published_at INTEGER NOT NULL
 )");
}
function boPublicInfoPayload(array $c):array {
 $add=[];$allowed=[];
 foreach(['arrival','schedule','parking','fees','turnigo','myscore'] as $k)$allowed[$k]=true;
 foreach(boInfoDefinitions() as $id=>$def){
  if(!isset($allowed[$id])||!in_array($id,boInfoSelected($c),true))continue;
  $value=trim((string)($c[$def['field']]??''));
  if($value==='')continue;
  if($def['kind']==='url'&&!boInfoValidHttps($value,$id))continue;
  $add[]=['label'=>$def['label'],'value'=>$value,'type'=>$def['kind']];
 }
 $guide=boGuideDecoded($c);
 foreach(boGuideDefinitions() as $id=>$def){
  if(in_array($id,['delay'],true)||!in_array($id,$guide['selected'],true))continue;
  $value=trim((string)($guide['values'][$id]??''));
  if($value==='')continue;
  if($def['kind']==='url'&&!boGuideSafeUrl($value))continue;
  $add[]=['label'=>$def['label'],'value'=>$value,'type'=>$def['kind']];
 }
 $venue=boVenueData((string)($c['venue_data']??'{}'));
 if(!empty($venue['_verified_at'])){
  foreach(['address','navigation','surface','checkin','parking','catering','facilities'] as $key){
   if(!isset(boVenueDefinitions()[$key]))continue;
   $value=trim((string)($venue[$key]??''));
   if($value==='')continue;
   if($key==='navigation'&&!boGuideSafeUrl($value))continue;
   $add[]=['label'=>boVenueDefinitions()[$key]['label'],'value'=>$value,'type'=>$key==='navigation'?'url':'text'];
  }
 }
 return [
  'version'=>1,'tournament_id'=>(string)$c['tournament_id'],
  'title'=>(string)$c['tournament_name'],
  'date'=>(string)$c['tournament_date'],
  'location'=>(string)$c['tournament_location'],
  'url'=>filter_var($c['tournament_url']??'',FILTER_VALIDATE_URL)?(string)$c['tournament_url']:'',
  'sections'=>$add,
 ];
}
function boPublicInfoPublish(PDO $db,array $campaign):void {
 $id=(int)$campaign['id'];
 if($campaign['state']!=='done'||!boCampaignDefaultsReviewed($campaign))
  throw new RuntimeException('Nur abgeschlossene und geprüfte Infomails dürfen öffentlich übernommen werden.');
 $q=$db->prepare("SELECT COUNT(*) FROM bo_deliveries WHERE campaign_id=? AND state='sent'");
 $q->execute([$id]);
 if((int)$q->fetchColumn()<1)throw new RuntimeException('Für diese Ausgabe wurde noch keine Mail erfolgreich vom Mailserver angenommen.');
 $payload=boPublicInfoPayload($campaign);
 if(!$payload['sections'])throw new RuntimeException('Keine geeigneten öffentlichen Hinweise vorhanden.');
 $stmt=$db->prepare("INSERT INTO bo_public_infos(tournament_id,campaign_id,payload,published_at) VALUES(?,?,?,?)
 ON CONFLICT(tournament_id) DO UPDATE SET campaign_id=excluded.campaign_id,payload=excluded.payload,published_at=excluded.published_at");
 $stmt->execute([$campaign['tournament_id'],$id,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),time()]);
}
