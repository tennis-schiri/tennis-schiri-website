<?php
declare(strict_types=1);

/* Reusable, manually selectable information blocks for organizational tournament emails. */
if(!defined('TS_BACKOFFICE')){http_response_code(404);exit;}

/** @return array<string,array{label:string,field:string,kind:string,help:string}> */
function boInfoDefinitions(): array {
 return [
  'arrival'=>['label'=>'Meldezeit & Treffpunkt','field'=>'info_arrival','kind'=>'text','help'=>'z. B. Bitte bis 09:30 Uhr bei der Turnierleitung melden.'],
  'schedule'=>['label'=>'Spielbeginn & Tagesablauf','field'=>'info_schedule','kind'=>'text','help'=>'z. B. Spielbeginn 10:00 Uhr, Einspielzeit 5 Minuten.'],
  'parking'=>['label'=>'Anfahrt & Parkmöglichkeiten','field'=>'info_parking','kind'=>'text','help'=>'z. B. Parkplätze hinter der Tennishalle.'],
  'fees'=>['label'=>'Nenngeld & Bezahlung','field'=>'info_fees','kind'=>'text','help'=>'z. B. 25 € Erwachsene, 15 € Jugendliche, bar bei Anmeldung.'],
  'turnigo'=>['label'=>'Turnigo – Turnierinformationen','field'=>'turnigo_url','kind'=>'url','help'=>'Vollständiger Turnigo-Link zu diesem Turnier, z. B. https://www.turnigo.de/…'],
  'myscore'=>['label'=>'MyScore.live – Ergebnisse','field'=>'myscore_url','kind'=>'url','help'=>'Direkter MyScore-Link zu diesem Turnier, z. B. https://myscore.live/…'],
  'contact'=>['label'=>'Kontakt am Turniertag','field'=>'info_contact','kind'=>'text','help'=>'z. B. Mobilnummer der Turnierleitung oder Hinweise zur Erreichbarkeit.'],
 ];
}
/** @return list<string> */
function boInfoSelected(array $campaign): array {
 $raw=json_decode((string)($campaign['info_sections']??'[]'),true);
 if(!is_array($raw))return [];
 $valid=boInfoDefinitions();$out=[];
 foreach(array_slice($raw,0,20) as $key){
  if(is_string($key)&&isset($valid[$key])&&!in_array($key,$out,true))$out[]=$key;
 }
 return $out;
}
function boInfoValidHttps(string $url,string $provider): bool {
 if(strlen($url)>500||preg_match('/[\r\n]/',$url)||!filter_var($url,FILTER_VALIDATE_URL))return false;
 $parts=parse_url($url);
 if(!is_array($parts)||strtolower((string)($parts['scheme']??''))!=='https'||isset($parts['user'])||isset($parts['pass']))return false;
 $host=strtolower((string)($parts['host']??''));
 $domain=$provider==='turnigo'?'turnigo.de':'myscore.live';
 return $host===$domain||str_ends_with($host,'.'.$domain);
}
/**
 * Parse and validate the POSTed checklist.
 * All values remain editable in the draft; only checked sections are included in outgoing mail.
 * @return array{info_sections:string,info_arrival:string,info_schedule:string,info_parking:string,info_fees:string,turnigo_url:string,myscore_url:string,info_contact:string}
 */
function boInfoFromPost(array $posted): array {
 $raw=$posted['info_sections']??[];
 if(!is_array($raw)||count($raw)>7)throw new InvalidArgumentException('Ungültige Themenauswahl.');
 $definitions=boInfoDefinitions();$selected=[];
 foreach($raw as $key){
  if(!is_string($key)||!isset($definitions[$key])||in_array($key,$selected,true)){
   throw new InvalidArgumentException('Die ausgewählten Infopunkte sind ungültig.');
  }
  $selected[]=$key;
 }
 $out=['info_sections'=>json_encode($selected,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)];
 foreach($definitions as $key=>$def){
  $value=$posted[$def['field']]??'';
  if(!is_string($value))throw new InvalidArgumentException('Ungültige Angabe: '.$def['label']);
  $value=trim($value);
  $max=$def['kind']==='url'?500:1500;
  if(strlen($value)>$max||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$value)){
   throw new InvalidArgumentException('Zu langer oder ungültiger Text: '.$def['label']);
  }
  if(in_array($key,$selected,true)){
   if($value==='')throw new InvalidArgumentException('Bitte den aktivierten Infopunkt ausfüllen: '.$def['label']);
   if($def['kind']==='url'&&!boInfoValidHttps($value,$key)){
    throw new InvalidArgumentException('Bitte einen gültigen HTTPS-Link auf '.($key==='turnigo'?'turnigo.de':'myscore.live').' eintragen.');
   }
  }
  // Link fields may be cached when unchecked, but still must never contain unsafe protocols.
  if($value!==''&&$def['kind']==='url'&&!boInfoValidHttps($value,$key)){
   throw new InvalidArgumentException('Ungültiger HTTPS-Link für '.$def['label']);
  }
  $out[$def['field']]=$value;
 }
 return $out;
}
function boInfoEditor(array $campaign=[]): string {
 $e=static fn($x)=>boEsc($x);
 $selected=boInfoSelected($campaign);
 $html='<fieldset class="panel" style="margin-top:19px;border-color:#c4dbba"><legend style="font-weight:900;color:#205438;padding:0 10px">Checkliste: Welche Informationen sollen in die Mail?</legend>';
 $html.='<p class="help">Nur angehakte Themen erscheinen als zusätzliche Infoblöcke. Fehlende Angaben werden nicht automatisch erfunden. Alle Angaben bleiben bis zur Freigabe bearbeitbar.</p>';
 $html.='<div class="grid">';
 foreach(boInfoDefinitions() as $key=>$def){
  $checked=in_array($key,$selected,true)?' checked':'';
  $value=$e($campaign[$def['field']]??'');
  $html.='<div class="tile" style="padding:15px;min-width:0"><label style="margin:0 0 10px;display:flex;gap:10px;align-items:flex-start"><input type="checkbox" name="info_sections[]" value="'.$e($key).'"'.$checked.' style="margin-top:4px;min-width:17px;height:17px"><span>'.$e($def['label']).'</span></label>';
  if($def['kind']==='url')$html.='<input type="url" name="'.$e($def['field']).'" maxlength="500" placeholder="'.$e($def['help']).'" value="'.$value.'">';
  else $html.='<textarea name="'.$e($def['field']).'" maxlength="1500" rows="3" style="min-height:85px" placeholder="'.$e($def['help']).'">'.$value.'</textarea>';
  $html.='</div>';
 }
 $html.='</div><p class="help">Bei Turnigo und MyScore.live den <strong>konkreten Link für dieses Turnier</strong> eintragen. Eine Anmeldung oder Live-Ergebnisanzeige wird nur angeboten, wenn der Punkt angehakt ist und der Link gültig ist.</p></fieldset>';
 return $html;
}
/** @return array{0:string,1:string} */
function boInfoMailBlocks(array $campaign): array {
 $selected=boInfoSelected($campaign);$definitions=boInfoDefinitions();
 if(!$selected)return ['',''];
 $html='<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:25px 0 23px"><tr><td><p style="font-size:11px;letter-spacing:1.5px;color:#3d8055;font-weight:800;margin:0 0 13px">WICHTIGE INFOS FÜR DICH</p></td></tr>';
 $plain="\n\nWICHTIGE INFOS FÜR DICH\n";
 foreach($selected as $key){
  $def=$definitions[$key];$value=trim((string)($campaign[$def['field']]??''));
  if($value==='')continue;
  if($def['kind']==='url'&&!boInfoValidHttps($value,$key))continue;
  $title=boEsc($def['label']);
  $text=$def['kind']==='url'
   ? '<a href="'.boEsc($value).'" target="_blank" style="color:#235e3a;font-size:14px;font-weight:800;text-decoration:underline">'.($key==='turnigo'?'Turnier auf Turnigo öffnen':'Live-Ergebnisse auf MyScore.live öffnen').' &#8599;</a>'
   :'<div style="font-size:14px;line-height:1.7;color:#355b46">'.nl2br(boEsc($value),false).'</div>';
  $html.='<tr><td style="padding:14px 18px;border:1px solid #dfe9d8;background:#f5f9f1"><p style="margin:0 0 5px;font-size:12px;font-weight:800;color:#1c4a31">'.$title.'</p>'.$text.'</td></tr><tr><td height="7" style="font-size:1px;line-height:7px">&nbsp;</td></tr>';
  $plain.="\n".$def['label'].":\n".$value."\n";
 }
 return [$html.'</table>',$plain];
}
