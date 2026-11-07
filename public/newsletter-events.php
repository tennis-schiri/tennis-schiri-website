<?php
declare(strict_types=1);
/* Read-only newsletter event catalog. No extra database or manual duplication. */
if (!defined('TS_NEWSLETTER_EVENTS')) { http_response_code(404); exit; }

/** @return array<int,array<string,string>> */
function newsletterEventsCatalog(): array {
 $file=__DIR__.'/data/newsletter-events.json';
 if(!is_file($file) || !is_readable($file)) return [];
 $data=json_decode((string)file_get_contents($file),true);
 if(!is_array($data) || ($data['version']??null)!==1 || !is_array($data['events']??null)) return [];
 $valid=[];
 foreach(array_slice($data['events'],0,100) as $row){
  if(!is_array($row))continue;
  $id=(string)($row['id']??'');
  $url=(string)($row['url']??'');
  $start=(string)($row['date_start']??'');
  $title=(string)($row['title']??'');
  $type=(string)($row['type']??'');
  if(!preg_match('/^[a-z0-9_-]{1,100}$/D',$id)||
     !preg_match('/^20\d{2}-\d{2}-\d{2}$/D',$start)||
     !in_array($type,['turnier','veranstaltung'],true)||
     $title===''||strlen($title)>400||
     !preg_match('~^https://[^[:space:]<>"]+$~iD',$url))continue;
  $valid[$id]=[
   'id'=>$id,'type'=>$type,'title'=>$title,
   'date_start'=>$start,'date'=>(string)($row['date']??''),
   'location'=>(string)($row['location']??''),
   'url'=>$url,'link_label'=>(string)($row['link_label']??'Details ansehen')
  ];
 }
 return array_values($valid);
}

/** @return array<int,array<string,string>> */
function newsletterStoredEvents(string $json): array {
 $decoded=json_decode($json,true);
 if(!is_array($decoded))return [];
 $rows=[];
 foreach(array_slice($decoded,0,25) as $row){
  if(!is_array($row))continue;
  $id=(string)($row['id']??'');
  $url=(string)($row['url']??'');
  if(!preg_match('/^[a-z0-9_-]{1,100}$/D',$id)||
     !preg_match('~^https://[^[:space:]<>"]+$~iD',$url))continue;
  $rows[$id]=[
   'id'=>$id,'type'=>in_array($row['type']??'', ['turnier','veranstaltung'],true)?$row['type']:'veranstaltung',
   'title'=>(string)($row['title']??''),
   'date_start'=>(string)($row['date_start']??''),
   'date'=>(string)($row['date']??''),
   'location'=>(string)($row['location']??''),
   'url'=>$url,'link_label'=>(string)($row['link_label']??'Details ansehen')
  ];
 }
 return array_values($rows);
}

/** Upcoming source events + previously saved events, never losing saved selections silently. */
function newsletterSelectableEvents(string $oldJson='[]'): array {
 $byId=[];
 $today=(new DateTimeImmutable('now',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
 $saved=newsletterStoredEvents($oldJson);
 foreach($saved as $row)$byId[$row['id']]=$row;
 foreach(newsletterEventsCatalog() as $row){
  if($row['date_start'] >= $today || isset($byId[$row['id']]))$byId[$row['id']]=$row;
 }
 uasort($byId,static fn($a,$b)=>strcmp($a['date_start'],$b['date_start'])?:strcmp($a['title'],$b['title']));
 return array_values($byId);
}

function newsletterEventSelector(string $oldJson='[]'): string {
 $existing=array_column(newsletterStoredEvents($oldJson),'id');
 $items=newsletterSelectableEvents($oldJson);
 $escaped=static fn($s):string=>htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
 $out='<fieldset class="newsletter-event-options"><legend>Turniere &amp; Veranstaltungen übernehmen</legend><p class="muted">Hake die gewünschten Termine an. Titel, Datum, Ort und der vorhandene Link zur Anmeldung oder Informationsseite werden automatisch übernommen. Diese Auswahl wird mit dem Entwurf gespeichert.</p>';
 if(!$items){
  return $out.'<p class="warning">Noch keine Termine aus der Website verfügbar. Nach dem nächsten Live-Upload wird die Auswahlliste automatisch erstellt.</p></fieldset>';
 }
 foreach(['turnier'=>'Turniere','veranstaltung'=>'Workshops & Veranstaltungen'] as $type=>$heading){
  $rows=array_values(array_filter($items,static fn($e)=>$e['type']===$type));
  if(!$rows)continue;
  $out.='<h3>'.$escaped($heading).'</h3><div class="newsletter-event-grid">';
  foreach($rows as $e){
   $checked=in_array($e['id'],$existing,true)?' checked':'';
   $isOld=$e['date_start']<(new DateTimeImmutable('now',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
   $stale=$isOld?'<span class="event-old">Vergangener Termin · bereits im Entwurf</span>':'';
   $out.='<div class="newsletter-event-option"><label class="event-check"><input type="checkbox" name="selected_events[]" value="'.$escaped($e['id']).'"'.$checked.'><span>'.
    '<strong>'.$escaped($e['title']).'</strong><small>'.$escaped($e['date']).' · '.$escaped($e['location']).'</small>'.$stale.
    '</span></label><a href="'.$escaped($e['url']).'" target="_blank" rel="noopener noreferrer">Link ansehen ↗</a></div>';
  }
  $out.='</div>';
 }
 return $out.'<p class="muted">Bei Terminen ohne veröffentlichte Ausschreibung verlinken wir auf die Turnier- bzw. Workshopseite – nicht auf eine erfundene Anmeldung. Aktualisierte Termine werden bei jedem Live-Upload übernommen. Ein gespeicherter Entwurf ändert sich erst, wenn du ihn erneut speicherst.</p></fieldset>';
}

/** @return array<int,array<string,string>> */
function newsletterResolveSelection(mixed $raw,string $oldJson='[]'): array {
 if(!is_array($raw) || count($raw)>25)return [];
 $available=[];
 foreach(newsletterSelectableEvents($oldJson) as $item)$available[$item['id']]=$item;
 $ids=array_values(array_unique(array_map('strval',$raw)));
 $selected=[];
 foreach($ids as $id) if(isset($available[$id])) $selected[]=$available[$id];
 return $selected;
}
