<?php
declare(strict_types=1);
if(!defined('TS_BACKOFFICE')){http_response_code(404);exit;}

/**
 * Source-backed, HISTORICAL examples only. Values are displayed as editable
 * proposals, never trusted as current without an explicit review.
 * Do not put prices, DTB sanctions, unknown addresses or secret access codes here.
 */
function boKnownVenueSuggestions(string $venueId): array {
 $sharedNeuenhagen=[
  'parking'=>"Parkmöglichkeiten an der Tennisanlage und an der Halle. Bitte nur ausgewiesene Plätze nutzen, vor der Hallenfront parallel parken und den Grünstreifen freihalten. Mit der S5 bis Neuenhagen, von dort zu Fuß (laut früheren Informationen etwa 15 Minuten).",
  'catering'=>"Gastronomie „Due Fratelli“ am Clubhaus. Bitte aktuelle Öffnungszeiten und Bezahlmöglichkeiten prüfen.",
  'facilities'=>"Umkleiden, Duschen und Toiletten im Clubhaus; bei Hallenturnieren auch in der Halle. Im Clubhaus bitte auf saubere Schuhe achten."
 ];
 $badSaarow=[
  'surface'=>"Hartplatz / Laykold. Bitte geeignete saubere Hallenschuhe und die aktuellen Platzvorgaben beachten.",
  'checkin'=>"Die Turnierleitung befindet sich nach früheren Informationen am Counter im Eingangsbereich.",
  'parking'=>"Kostenlose Parkplätze nach früheren Infos hinter der Schranke. Ausfahrt-Ticket ggf. bei Turnierleitung/Gastronomie erhältlich; Feuerwehrzufahrt und Mitarbeiterstellplätze freihalten.",
  'catering'=>"Gastronomie mit möglichen Verzehrbons; Umfang und Ausgabe richten sich nach der aktuellen Turnierausschreibung."
 ];
 return match($venueId){
  'bad-saarow'=>$badSaarow,
  'neuenhagen'=>$sharedNeuenhagen,
  'neuenhagen-halle'=>array_merge($sharedNeuenhagen,[
   'surface'=>"Hallenbelag nach bisherigen Infos: Advantage Red Court; saubere Sandplatzschuhe waren zugelassen. Aktuelle Schuhvorgaben prüfen.",
   'checkin'=>"Turnierleitung bei früheren Hallenturnieren im Vorraum der Halle. Treffpunkt für dieses Turnier bestätigen."
  ]),
  'neuenhagen-aussen'=>array_merge($sharedNeuenhagen,[
   'facilities'=>"Umkleiden, Duschen und Toiletten im Clubhaus (rechts am Clubhaus). Bitte auf saubere Schuhe beim Betreten achten."
  ]),
  default=>[]
 };
}
function boKnownVenueSource(string $venueId):string {
 return match($venueId){
  'bad-saarow'=>'Frühere Bad-Saarow-Turniermails; aktuelle Prüfung erforderlich.',
  'neuenhagen','neuenhagen-halle','neuenhagen-aussen'=>'Historische Infomails NTC / NTC93 (2025/2026), dokumentiert unter docs/standorte/neuenhagen-quellenprofil.md. Aktuelle Prüfung erforderlich.',
  default=>''
 };
}
function boVenueBestGuess(array $tournament):string {
 $haystack=strtolower((string)($tournament['location']??'').' '.(string)($tournament['title']??''));
 if(str_contains($haystack,'saarow'))return 'bad-saarow';
 if(str_contains($haystack,'neuenhagen')||str_contains($haystack,'ntc')){
  if(str_contains($haystack,'halle')||str_contains($haystack,'hallen')||str_contains($haystack,'winter'))return 'neuenhagen-halle';
  if(str_contains($haystack,'außen')||str_contains($haystack,'aussen')||str_contains($haystack,'freiplatz')||str_contains($haystack,'sandplatz'))return 'neuenhagen-aussen';
  return 'neuenhagen';
 }
 return 'general';
}
/** Candidate copy used on drafts; confirmed saved profiles are never silently changed. */
function boVenueDraftSuggestions(string $venueId,string $currentJson='{}'): array {
 $current=boVenueData($currentJson);
 if(!empty($current['_verified_at']))return $current;
 foreach(boKnownVenueSuggestions($venueId) as $key=>$suggestion) {
  if(trim((string)($current[$key]??''))==='')$current[$key]=$suggestion;
 }
 if(empty($current['_source'])&&boKnownVenueSource($venueId)!=='')$current['_source']=boKnownVenueSource($venueId);
 unset($current['_verified_at']);
 return $current;
}
function boKnownInfoDefaults(string $venueId,string $type='info'):array {
 if($type==='update'){
  return [
   'info_sections'=>json_encode(['contact'],JSON_THROW_ON_ERROR),
   'info_contact'=>"Rückfragen zur Turnierorganisation: turniere@tennis-schiri.de."
  ];
 }
 $fields=[
  'info_arrival'=>"Bitte sei etwa 30 Minuten vor der angesetzten Spielzeit an der Anlage und melde dich bei der Turnierleitung. Für einzelne Frühspiele können andere Meldezeiten gelten; bitte die aktuellen Angaben beachten.",
  'info_contact'=>"Bei organisatorischen Rückfragen erreichst du die Turnierleitung über turniere@tennis-schiri.de."
 ];
 if($type==='reminder')$fields['info_arrival']="Bitte kontrolliere deine aktuelle Spielansetzung und sei rechtzeitig vor Spielbeginn vor Ort. In bisherigen Turnieren galt oft die Empfehlung, etwa 30 Minuten vor der Spielzeit anwesend zu sein; abweichende Meldezeiten haben Vorrang.";
 $fields['info_sections']=json_encode(['arrival','contact'],JSON_THROW_ON_ERROR);
 return $fields;
}
function boKnownGuideDefaults(string $venueId,string $type='info'):string {
 $values=[];$selected=[];
 $put=static function(string $key,string $value) use (&$values,&$selected):void {
  $values[$key]=$value;$selected[]=$key;
 };
 if($type==='update'){
  $put('draw_changes','Bitte prüfe aktuelle Änderungen über den offiziellen Turnierlink und die Mitteilung der Turnierleitung.');
 }else{
  if(in_array($venueId,['bad-saarow','neuenhagen','neuenhagen-halle','neuenhagen-aussen'],true)){
   $put('warmup','Bei früheren Turnieren waren fünf Minuten Einspielzeit vorgesehen. Bitte mit der aktuellen Ausschreibung abgleichen.');
  }
  $put('results','Bitte das Ergebnis direkt im Anschluss an dein Match bei der Turnierleitung melden, sofern vor Ort nichts anderes angegeben wird.');
  if($type==='info'){
   $put('breaks','Zwischen den Matches kann es zu Wartezeiten kommen. Bitte melde dich bei der Turnierleitung, falls du die Anlage vorübergehend verlassen möchtest.');
   $put('draw_changes','Prüfe die aktuelle Ansetzung möglichst am Vorabend und noch einmal am Turniertag. Änderungen sind möglich; verbindlich sind die aktuellen offiziellen Informationen.');
   $put('withdrawal','Für Rücktritt oder Nichtantreten gelten die aktuelle Ausschreibung und die jeweils gültigen DTB-Regelungen. Bitte die Turnierleitung umgehend informieren.');
  }
 }
 return json_encode(['selected'=>$selected,'values'=>$values],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}
/** @return array<string,mixed> */
function boKnownCampaignDefaults(string $venueId,string $currentVenueJson='{}',string $type='info'):array {
 $venue=boVenueDraftSuggestions($venueId,$currentVenueJson);
 return array_merge(
  ['venue_data'=>json_encode($venue,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
   'guide_data'=>boKnownGuideDefaults($venueId,$type),
   'defaults_review_required'=>1,'defaults_reviewed_at'=>null],
  boKnownInfoDefaults($venueId,$type)
 );
}
/** Fill only absent values; never overwrite a user's existing draft text or custom selections. */
function boMergeKnownCampaign(array $existing,string $venueId):array {
 $defaults=boKnownCampaignDefaults($venueId,(string)($existing['venue_data']??'{}'),(string)($existing['template_type']??'info'));
 $new=$existing;
 $oldInfo=boInfoSelected($existing);
 $wanted=boInfoSelected($defaults);
 foreach(boInfoDefinitions() as $key=>$def){
  $field=$def['field'];
  if(trim((string)($new[$field]??''))===''&&trim((string)($defaults[$field]??''))!==''){
   $new[$field]=$defaults[$field];
   if(in_array($key,$wanted,true))$oldInfo[]=$key;
  }
 }
 $new['info_sections']=json_encode(array_values(array_unique($oldInfo)),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 $oldGuide=boGuideDecoded($existing);
 $addGuide=boGuideDecoded($defaults);
 foreach($addGuide['values'] as $key=>$value){
  if($value!==''&&trim((string)($oldGuide['values'][$key]??''))===''){
   $oldGuide['values'][$key]=$value;
   if(in_array($key,$addGuide['selected'],true))$oldGuide['selected'][]=$key;
  }
 }
 $oldGuide['selected']=array_values(array_unique($oldGuide['selected']));
 $new['guide_data']=json_encode($oldGuide,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 $new['venue_data']=$defaults['venue_data'];
 $new['defaults_review_required']=1;
 $new['defaults_reviewed_at']=null;
 return $new;
}
/** Separate form for the event-specific venue snapshot: no global master overwrite. */
function boCampaignVenueEditor(array $campaign):string {
 $data=boVenueData((string)($campaign['venue_data']??'{}'));
 $status=empty($data['_verified_at'])?'<p class="warning">Vorschläge aus früheren Infomails. Erst nach deiner aktuellen Prüfung werden sie für die echte Aussendung freigegeben.</p>':'<p class="notice">Standortdaten für diesen Entwurf wurden bereits überprüft. Bei Änderungen bitte erneut bestätigen.</p>';
 $out='<fieldset class="panel" style="border-color:#c8d9bf"><legend style="font-weight:900;padding:0 10px">Standort & Anlage · vorausgefüllte Angaben</legend>'.$status;
 $out.='<p class="help">Diese Angaben gelten nur für die aktuelle E-Mail. Änderungen an dieser Stelle verändern keine globalen Standortvorlagen.</p>';
 foreach(boVenueDefinitions() as $key=>$def){
  $value=boEsc($data[$key]??'');
  $out.='<label>'.boEsc($def['label']).'</label>';
  if($def['kind']==='url')$out.='<input type="url" name="campaign_venue_'.$key.'" maxlength="500" value="'.$value.'" placeholder="'.boEsc($def['help']).'">';
  else $out.='<textarea name="campaign_venue_'.$key.'" rows="2" maxlength="1000" style="min-height:65px">'.$value.'</textarea>';
 }
 $out.='<p class="help">Interne Herkunft: '.boEsc($data['_source']??'nicht hinterlegt').'</p></fieldset>';
 return $out;
}
function boCampaignVenueFromPost(array $post,array $campaign,bool $confirmed):string {
 $source=boVenueData((string)($campaign['venue_data']??'{}'));
 $mapped=['venue_source'=>$source['_source']??''];
 foreach(boVenueDefinitions() as $key=>$def)$mapped['venue_'.$key]=$post['campaign_venue_'.$key]??'';
 if($confirmed)$mapped['venue_verified']='yes';
 $data=boVenueValidate($mapped);
 return json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}
