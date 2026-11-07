<?php
declare(strict_types=1);
if(!defined('TS_BACKOFFICE')){http_response_code(404);exit;}

/* Extended tournament guide 2.2 modules. Data is stored as JSON on each campaign.
   No values are invented. Only checked and nonempty sections appear in email. */
function boGuideDefinitions():array {
 return [
  'urgent'=>['group'=>'Aktuelle Hinweise','label'=>'Wichtige Änderung / aktueller Hinweis','kind'=>'text','hint'=>'z. B. geänderte Zufahrt oder Spielbeginn'],
  'draw'=>['group'=>'Dein Turnier','label'=>'Offizielle Auslosung / Spielplan','kind'=>'url','hint'=>'Konkreter HTTPS-Link auf tennis.de oder die offizielle Turnierseite'],
  'delay'=>['group'=>'Anreise & Anmeldung','label'=>'Bei Verspätung','kind'=>'text','hint'=>'Telefon oder konkreter Kontakt am Turniertag'],
  'shoes'=>['group'=>'Auf dem Court','label'=>'Belag und geeignete Schuhe','kind'=>'text','hint'=>'Belag und Vorschriften zu Schuhen'],
  'scoring'=>['group'=>'Auf dem Court','label'=>'Spielmodus / Zählweise','kind'=>'text','hint'=>'z. B. Kurzsätze, Match-Tiebreak – jeweils nach Ausschreibung'],
  'warmup'=>['group'=>'Auf dem Court','label'=>'Einspielzeit','kind'=>'text','hint'=>'z. B. 5 Minuten – vor Versand prüfen'],
  'results'=>['group'=>'Auf dem Court','label'=>'Ergebnismeldung','kind'=>'text','hint'=>'Wer meldet Ergebnisse, wo und wann?'],
  'breaks'=>['group'=>'Auf dem Court','label'=>'Pausen, Wartezeiten und Anlage verlassen','kind'=>'text','hint'=>'z. B. bei der Turnierleitung abmelden'],
  'balls'=>['group'=>'Auf dem Court','label'=>'Spielbälle / Ballmarke','kind'=>'text','hint'=>'Nur falls für das Turnier gesichert'],
  'fees_detail'=>['group'=>'Nenngeld & Service','label'=>'Gebühren je Konkurrenz','kind'=>'text','hint'=>'Eine Zeile pro Konkurrenz, ggf. DTB-Gebühr/Verzehrbon'],
  'payment'=>['group'=>'Nenngeld & Service','label'=>'Zahlungsarten','kind'=>'text','hint'=>'Bar/Karte/etc. nur nach aktueller Prüfung'],
  'catering'=>['group'=>'Nenngeld & Service','label'=>'Gastronomie / Verzehrbons','kind'=>'text','hint'=>'Angebot und Bedingungen'],
  'facilities'=>['group'=>'Nenngeld & Service','label'=>'Umkleiden und Duschen','kind'=>'text','hint'=>'Wenn vorhanden und relevant'],
  'draw_changes'=>['group'=>'Wichtige Regelungen','label'=>'Auslosungsstatus / Änderungen','kind'=>'text','hint'=>'Nur aktuelle, bestätigte Regelungen'],
  'withdrawal'=>['group'=>'Wichtige Regelungen','label'=>'Rücktritt und Nichtantreten','kind'=>'text','hint'=>'Kein unbelegter pauschaler LK-Strafpunkte-Hinweis'],
 ];
}
function boGuideDecoded(array $campaign):array {
 $obj=json_decode((string)($campaign['guide_data']??'{}'),true);
 if(!is_array($obj))return ['selected'=>[],'values'=>[]];
 $definitions=boGuideDefinitions();
 $keys=[];
 foreach((array)($obj['selected']??[]) as $key){
  if(is_string($key)&&isset($definitions[$key])&&!in_array($key,$keys,true))$keys[]=$key;
 }
 $values=[];
 foreach($definitions as $key=>$def){
  $v=($obj['values']??[])[$key]??'';
  $values[$key]=is_string($v)?substr($v,0,1500):'';
 }
 return ['selected'=>$keys,'values'=>$values];
}
function boGuideFromPost(array $post):string {
 $selected=$post['guide_sections']??[];
 $definitions=boGuideDefinitions();
 if(!is_array($selected)||count($selected)>count($definitions))throw new InvalidArgumentException('Ungültige Auswahl im Turnier-Guide.');
 $keys=[];$values=[];
 foreach($selected as $key){
  if(!is_string($key)||!isset($definitions[$key])||in_array($key,$keys,true))throw new InvalidArgumentException('Unbekannter Guide-Baustein.');
  $keys[]=$key;
 }
 foreach($definitions as $key=>$def){
  $v=$post['guide_'.$key]??'';
  if(!is_string($v))throw new InvalidArgumentException('Ungültiger Inhalt bei '.$def['label']);
  $v=trim($v);
  $max=$def['kind']==='url'?500:1500;
  if(strlen($v)>$max||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$v))throw new InvalidArgumentException('Bitte den Inhalt von '.$def['label'].' prüfen.');
  if($def['kind']==='url'&&$v!==''&&!boGuideSafeUrl($v))throw new InvalidArgumentException('Für '.$def['label'].' muss ein gültiger HTTPS-Link eingetragen werden.');
  if(in_array($key,$keys,true)&&$v==='')throw new InvalidArgumentException('Bitte den aktiven Baustein ausfüllen: '.$def['label']);
  $values[$key]=$v;
 }
 return json_encode(['selected'=>$keys,'values'=>$values],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}
function boGuideEditor(array $campaign):string {
 $data=boGuideDecoded($campaign);$out='';
 $groups=[];
 foreach(boGuideDefinitions() as $key=>$def)$groups[$def['group']][$key]=$def;
 $out.='<fieldset class="panel" style="border:1px solid #c6d9be"><legend style="font-weight:900;padding:0 10px;color:#295a3e">Turnier-Guide 2.2 · Detailbausteine</legend>';
 $out.='<p class="help">Zusätzlich zur kurzen Checkliste: detaillierte, nach Teilnehmerablauf sortierte Informationen. Nur angehakte und ausgefüllte Inhalte werden eingefügt.</p>';
 foreach($groups as $title=>$items){
  $opened=array_intersect(array_keys($items),$data['selected'])?' open':'';
  $out.='<details'.$opened.' style="border:1px solid #d9e5d5;border-radius:9px;margin:10px 0;padding:13px;background:#fff"><summary style="font-weight:800;cursor:pointer;color:#235537">'.boEsc($title).'</summary><div class="grid" style="padding-top:10px">';
  foreach($items as $key=>$def){
   $checked=in_array($key,$data['selected'],true)?' checked':'';
   $value=boEsc($data['values'][$key]??'');
   $out.='<div class="tile" style="padding:13px"><label style="display:flex;align-items:flex-start;gap:9px;margin:0 0 9px"><input type="checkbox" name="guide_sections[]" value="'.boEsc($key).'"'.$checked.'><span>'.boEsc($def['label']).'</span></label>';
   if($def['kind']==='url')$out.='<input type="url" name="guide_'.$key.'" maxlength="500" placeholder="'.boEsc($def['hint']).'" value="'.$value.'">';
   else $out.='<textarea name="guide_'.$key.'" rows="3" maxlength="1500" style="min-height:86px" placeholder="'.boEsc($def['hint']).'">'.$value.'</textarea>';
   $out.='</div>';
  }
  $out.='</div></details>';
 }
 return $out.'<p class="help">Rechts- und Gebührenangaben immer mit der aktuellen Ausschreibung und den gültigen DTB-Regelungen abgleichen. Keine automatisch unterstellten Strafen oder Zahlungsarten.</p></fieldset>';
}
function boGuideMailBlocks(array $campaign):array {
 $data=boGuideDecoded($campaign);
 if(!$data['selected'])return ['',''];
 $groups=[];$plain="\n\nTURNIER-GUIDE\n";
 foreach(boGuideDefinitions() as $key=>$def){
  if(!in_array($key,$data['selected'],true))continue;
  $v=trim($data['values'][$key]??'');
  if($v===''||($def['kind']==='url'&&!boGuideSafeUrl($v)))continue;
  $groups[$def['group']][]=['label'=>$def['label'],'value'=>$v,'kind'=>$def['kind']];
 }
 $html='';
 foreach($groups as $group=>$items){
  $alert=$group==='Aktuelle Hinweise';
  $bg=$alert?'#fff5e8':'#f3f8ee';
  $fg=$alert?'#8d572a':'#326443';
  $html.='<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:17px 0 21px;background:'.$bg.';border:1px solid '.($alert?'#f0d2aa':'#dce8d2').'"><tr><td style="padding:18px 18px 12px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:800;letter-spacing:1.4px;color:'.$fg.'">'.boEsc($group).'</td></tr>';
  $plain.="\n".$group."\n";
  foreach($items as $item){
   $value=$item['kind']==='url'?'<a href="'.boEsc($item['value']).'" style="font-weight:800;color:#22623d;text-decoration:underline">Spielplan/Details öffnen &#8599;</a>':nl2br(boEsc($item['value']),false);
   $html.='<tr><td style="padding:9px 18px 13px;border-bottom:1px solid #e1eadc"><p style="margin:0 0 4px;font-size:11px;font-weight:800;color:'.$fg.'">'.boEsc($item['label']).'</p><p style="margin:0;font-size:14px;line-height:1.65;color:#315440">'.$value.'</p></td></tr>';
   $plain.=$item['label'].":\n".$item['value']."\n";
  }
  $html.='</table>';
 }
 return [$html,$plain];
}
