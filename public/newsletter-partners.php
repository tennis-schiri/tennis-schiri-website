<?php
declare(strict_types=1);
/* Read-only partner and project catalog. All public entrypoints are blocked. */
if (!defined('TS_NEWSLETTER_PARTNERS')) { http_response_code(404); exit; }

function newsletterPartnerUrl(string $url): bool {
 if (strlen($url)>500 || preg_match('/[\r\n]/',$url)) return false;
 $parts=parse_url($url);
 return is_array($parts) && strtolower((string)($parts['scheme']??''))==='https'
   && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']);
}
function newsletterPartnerLogo(string $url): bool {
 if (!newsletterPartnerUrl($url)) return false;
 $parts=parse_url($url);
 $host=strtolower((string)$parts['host']);
 $path=(string)($parts['path']??'');
 return ($host==='tennis-schiri.de' && str_starts_with($path,'/assets/partner/')
     && preg_match('/^\/assets\/partner\/[a-z0-9-]+\.(?:png|jpg|jpeg|webp)$/D',$path))
   || ($host==='tennilo.de' && $path==='/assets/tennilo-logo.png');
}

/** @return array<int,array<string,string>> */
function newsletterPartnersCatalog(): array {
 $file=__DIR__.'/data/newsletter-partners.json';
 if(!is_readable($file))return [];
 $decoded=json_decode((string)file_get_contents($file),true);
 if(!is_array($decoded)||($decoded['version']??null)!==1||!is_array($decoded['partners']??null))return [];
 $partners=[];
 foreach(array_slice($decoded['partners'],0,30) as $row){
  if(!is_array($row))continue;
  $id=(string)($row['id']??'');
  $name=(string)($row['name']??'');
  $url=(string)($row['url']??'');
  $logo=(string)($row['logo']??'');
  $category=(string)($row['category']??'');
  if(!preg_match('/^[a-z0-9-]{1,95}$/D',$id) ||
     !in_array($category,['partner','projekt'],true) ||
     $name==='' || strlen($name)>180 ||
     !newsletterPartnerUrl($url) || !newsletterPartnerLogo($logo))continue;
  $partners[$id]=[
   'id'=>$id,'category'=>$category,'name'=>$name,'url'=>$url,
   'logo'=>$logo,'caption'=>mb_strcut((string)($row['caption']??''),0,180,'UTF-8'),
  ];
 }
 return array_values($partners);
}

/** @return array<int,array<string,string>> */
function newsletterStoredPartners(string $json): array {
 $decoded=json_decode($json,true);
 if(!is_array($decoded))return [];
 $rows=[];
 foreach(array_slice($decoded,0,12) as $row){
  if(!is_array($row))continue;
  $id=(string)($row['id']??'');
  $url=(string)($row['url']??'');
  $logo=(string)($row['logo']??'');
  $name=(string)($row['name']??'');
  if(!preg_match('/^[a-z0-9-]{1,95}$/D',$id)||!newsletterPartnerUrl($url)||
     !newsletterPartnerLogo($logo)||$name===''||strlen($name)>180)continue;
  $rows[$id]=[
   'id'=>$id,'category'=>($row['category']??'')==='projekt'?'projekt':'partner',
   'name'=>$name,'url'=>$url,'logo'=>$logo,
   'caption'=>(string)($row['caption']??''),
  ];
 }
 return array_values($rows);
}

function newsletterSelectablePartners(string $savedJson='[]'): array {
 $byId=[];
 foreach(newsletterStoredPartners($savedJson) as $row)$byId[$row['id']]=$row;
 foreach(newsletterPartnersCatalog() as $row)$byId[$row['id']]=$row;
 return array_values($byId);
}

function newsletterPartnersSelector(string $savedJson='[]'): string {
 $selected=array_column(newsletterStoredPartners($savedJson),'id');
 $items=newsletterSelectablePartners($savedJson);
 $esc=static fn(mixed $s):string=>htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
 $out='<fieldset class="newsletter-event-options newsletter-partner-selector"><legend>Partner &amp; TENNILO einblenden</legend><p class="muted">Wähle die Partner oder TENNILO aus, die als kleine, anklickbare Logo-Banner in dieser Ausgabe erscheinen sollen. Keine Auswahl bedeutet keine Banner.</p>';
 if(!$items)return $out.'<p class="warning">Die Logo-Liste ist noch nicht verfügbar. Sie wird beim nächsten Live-Upload aus der Partnerseite erzeugt.</p></fieldset>';
 foreach(['partner'=>'Unsere Partner','projekt'=>'TENNILO · eigenes Projekt'] as $type=>$heading){
  $subset=array_values(array_filter($items,static fn($e)=>$e['category']===$type));
  if(!$subset)continue;
  $out.='<h3>'.$esc($heading).'</h3><div class="newsletter-event-grid newsletter-partner-grid">';
  foreach($subset as $item){
    $checked=in_array($item['id'],$selected,true)?' checked':'';
    $out.='<div class="newsletter-event-option newsletter-partner-option"><label class="event-check"><input type="checkbox" name="selected_partners[]" value="'.$esc($item['id']).'"'.$checked.'>'.
      '<span class="partner-choice"><img src="'.$esc($item['logo']).'" alt="" loading="lazy" width="95" height="48">'.
      '<span><strong>'.$esc($item['name']).'</strong><small>'.$esc($item['caption']).'</small></span></span></label>'.
      '<a href="'.$esc($item['url']).'" target="_blank" rel="noopener noreferrer">Website ansehen ↗</a></div>';
  }
  $out.='</div>';
 }
 return $out.'<p class="muted">Die Logos werden in E-Mails von den jeweiligen Websites geladen und können je nach Postfach zunächst ausgeblendet sein. Partnernamen und Links bleiben trotzdem lesbar. Die Auswahl wird im Entwurf gespeichert und nicht automatisch an spätere Änderungen angepasst.</p></fieldset>';
}

/** @return array<int,array<string,string>> */
function newsletterResolvePartners(mixed $raw,string $savedJson='[]'): array {
 if(!is_array($raw)||count($raw)>12)return [];
 $ids=array_values(array_unique(array_filter($raw,'is_string')));
 $available=[];
 foreach(newsletterSelectablePartners($savedJson) as $row)$available[$row['id']]=$row;
 $out=[];
 foreach($ids as $id) if(isset($available[$id]))$out[]=$available[$id];
 return $out;
}
