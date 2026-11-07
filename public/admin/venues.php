<?php
declare(strict_types=1);
if(!defined('TS_BACKOFFICE')){http_response_code(404);exit;}
function boVenueDefinitions(): array {
 return [
  'address'=>['label'=>'Vollständige Anschrift','kind'=>'text','help'=>'Straße, Hausnummer, PLZ und Ort'],
  'navigation'=>['label'=>'Navigationslink','kind'=>'url','help'=>'https://maps.google.com/… oder andere konkrete Karte'],
  'surface'=>['label'=>'Platzbelag und passende Schuhe','kind'=>'text','help'=>'Vor Ort prüfen, nicht ungefragt vorausfüllen'],
  'checkin'=>['label'=>'Turnierleitung / Anmeldung','kind'=>'text','help'=>'z. B. Ort des Counters oder der Turnierleitung'],
  'parking'=>['label'=>'Parkmöglichkeiten','kind'=>'text','help'=>'Parkplätze, Zufahrt, ggf. Schranke/Ausfahrt'],
  'catering'=>['label'=>'Gastronomie und Verzehrbons','kind'=>'text','help'=>'Öffnungszeiten oder Bedingungen prüfen'],
  'facilities'=>['label'=>'Umkleiden und Duschen','kind'=>'text','help'=>'Ausstattung, sofern relevant'],
  'contact'=>['label'=>'Ansprechpartner / Telefonnummer','kind'=>'text','help'=>'Direkter Kontakt nur bei tatsächlicher Freigabe'],
 ];
}
function boVenueSchema(PDO $db): void {
 $db->exec("CREATE TABLE IF NOT EXISTS bo_venues (id TEXT PRIMARY KEY, name TEXT NOT NULL, data TEXT NOT NULL DEFAULT '{}', updated_at INTEGER NOT NULL DEFAULT 0)");
 foreach(['general'=>'Allgemeine Vorlage','bad-saarow'=>'Tennis Center Bad Saarow',
          'neuenhagen'=>'Neuenhagen (übergreifend)',
          'neuenhagen-halle'=>'Neuenhagen · Halle',
          'neuenhagen-aussen'=>'Neuenhagen · Außenanlage'] as $id=>$name){
  $q=$db->prepare("INSERT OR IGNORE INTO bo_venues(id,name,data,updated_at) VALUES(?,?,'{}',0)");
  $q->execute([$id,$name]);
 }
}
function boVenueData(string $json):array {
 $raw=json_decode($json,true);
 if(!is_array($raw))return [];
 $out=[];
 foreach(boVenueDefinitions() as $key=>$spec){
  $v=$raw[$key]??'';
  if(is_string($v)&&strlen($v)<=1000)$out[$key]=$v;
 }
 if(is_string($raw['_verified_at']??null)&&preg_match('/^20\d\d-\d\d-\d\d$/D',$raw['_verified_at']))$out['_verified_at']=$raw['_verified_at'];
 if(is_string($raw['_source']??null))$out['_source']=substr($raw['_source'],0,500);
 return $out;
}
function boVenueValidate(array $post):array {
 $out=[];
 foreach(boVenueDefinitions() as $key=>$spec){
  $raw=$post['venue_'.$key]??'';
  if(!is_string($raw))throw new InvalidArgumentException('Ungültiges Standortfeld: '.$spec['label']);
  $v=trim($raw);
  if(strlen($v)>1000||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$v))throw new InvalidArgumentException('Ungültige oder zu lange Standortangabe: '.$spec['label']);
  if($spec['kind']==='url'&&$v!==''&&!boGuideSafeUrl($v))throw new InvalidArgumentException('Der Navigationslink muss eine gültige HTTPS-Adresse sein.');
  $out[$key]=$v;
 }
 $source=$post['venue_source']??'';
 if(!is_string($source)||strlen($source)>500||preg_match('/[\x00-\x1F]/',$source))throw new InvalidArgumentException('Bitte interne Quellenangabe prüfen.');
 $out['_source']=trim($source);
 $confirmed=($post['venue_verified']??'')==='yes';
 $out['_verified_at']=$confirmed?gmdate('Y-m-d'):'';
 return $out;
}
function boGuideSafeUrl(string $url):bool {
 if(strlen($url)>500||preg_match('/[\r\n]/',$url)||!filter_var($url,FILTER_VALIDATE_URL))return false;
 $p=parse_url($url);
 return is_array($p)&&strtolower((string)($p['scheme']??''))==='https'
   && isset($p['host'])&&!isset($p['user'])&&!isset($p['pass']);
}
function boVenueList(PDO $db):array {
 $all=$db->query('SELECT * FROM bo_venues ORDER BY CASE id WHEN "general" THEN 0 WHEN "bad-saarow" THEN 1 WHEN "neuenhagen" THEN 2 WHEN "neuenhagen-halle" THEN 3 WHEN "neuenhagen-aussen" THEN 4 ELSE 5 END, name')->fetchAll();
 return array_column($all,null,'id');
}
function boVenueSelector(array $venues,string $current='auto',bool $withAuto=true):string {
 $out='<select name="venue_id" required>';
 if($withAuto)$out.='<option value="auto"'.($current==='auto'?' selected':'').'>Automatisch anhand von Turniername und Ort</option>';
 foreach($venues as $id=>$v)$out.='<option value="'.boEsc($id).'"'.($id===$current?' selected':'').'>'.boEsc($v['name']).'</option>';
 return $out.'</select>';
}
function boVenueEditor(array $profile):string {
 $data=boVenueDraftSuggestions((string)($profile['id']??'general'),(string)($profile['data']??'{}'));
 $verified=$data['_verified_at']??'';
 $out='<p class="help">Bereits bekannte Angaben aus bisherigen Infomails sind hier als <strong>bearbeitbare Vorschläge</strong> vorausgefüllt. Bitte anhand des aktuellen Turniers prüfen, korrigieren und erst dann unten bestätigen. Änderungen wirken nur auf neu erstellte Infomails.</p>';
 $out.='<p class="'.($verified?'notice':'warning').'">'.($verified?'Zuletzt geprüft am '.boEsc($verified):'Noch nicht als aktueller Stand bestätigt. Daten werden nicht in Teilnehmermails übernommen, bis du sie erneut speicherst und bestätigst.').'</p>';
 if(str_starts_with((string)($profile['id']??''),'neuenhagen'))$out.='<p class="help">Die früheren Neuenhagen-Mails wurden als <a href="https://github.com/tennis-schiri/tennis-schiri-website/blob/main/docs/standorte/neuenhagen-quellenprofil.md" target="_blank" rel="noopener">Quellenprofil</a> ausgewertet. Halle und Außenanlage bitte getrennt prüfen; alte Nenngelder, Beläge oder Zugangshinweise nicht ungeprüft übernehmen.</p>';
 foreach(boVenueDefinitions() as $key=>$def){
  $out.='<label>'.boEsc($def['label']).'</label>';
  if($def['kind']==='url')$out.='<input type="url" name="venue_'.$key.'" maxlength="500" value="'.boEsc($data[$key]??'').'" placeholder="'.boEsc($def['help']).'">';
  else $out.='<textarea name="venue_'.$key.'" rows="2" maxlength="1000" style="min-height:70px" placeholder="'.boEsc($def['help']).'">'.boEsc($data[$key]??'').'</textarea>';
 }
 $out.='<label>Quellenhinweis (nur intern)</label><input type="text" name="venue_source" maxlength="500" value="'.boEsc($data['_source']??'').'" placeholder="z. B. telefonisch mit Anlage am … bestätigt">';
 $out.='<label style="display:flex;align-items:center;gap:10px"><input type="checkbox" name="venue_verified" value="yes"> Ich habe die Angaben für kommende Turniermails aktuell geprüft.</label>';
 $out.='<p class="help">Die Bestätigung gilt nur für diese gespeicherte Version. Bei jeder Änderung ist sie erneut erforderlich. Ohne Bestätigung werden diese Angaben im Mail-Layout nicht angezeigt.</p>';
 return $out;
}
function boVenueMailBlocks(array $campaign):array {
 $data=boVenueData((string)($campaign['venue_data']??'{}'));
 if(empty($data['_verified_at']))return ['',''];
 $html='';$text='';
 $labels=boVenueDefinitions();
 foreach($data as $key=>$value){
  if(!isset($labels[$key]))continue; // internal verification/source metadata never goes into messages
  $value=trim($value);
  if($value==='')continue;
  if($labels[$key]['kind']==='url'&&!boGuideSafeUrl($value))continue;
  $label=$labels[$key]['label'];
  $inside=$key==='navigation'?'<a href="'.boEsc($value).'" style="font-weight:bold;color:#1d6541;text-decoration:underline">Route öffnen &#8599;</a>':nl2br(boEsc($value),false);
  $html.='<tr><td style="padding:10px 17px 12px;border-bottom:1px solid #e2ebdb"><p style="font-size:11px;font-weight:800;color:#537d57;margin:0 0 4px">'.boEsc($label).'</p><p style="font-size:14px;line-height:1.6;color:#235239;margin:0">'.$inside.'</p></td></tr>';
  $text.="\n".$label.": ".$value;
 }
 if($html==='')return ['',''];
 return [
 '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f4f8ef;margin:25px 0;border:1px solid #d9e8d1"><tr><td style="padding:18px 18px 8px;font-size:11px;letter-spacing:1.4px;font-weight:800;color:#336747">ANREISE & ANLAGE</td></tr>'.$html.'</table>',
 "\n\nANREISE & ANLAGE".$text."\n"
 ];
}
