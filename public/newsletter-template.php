<?php
declare(strict_types=1);
/* Nur serverseitig von der geschützten Newsletter-Verwaltung einbinden. */
if (!defined('TS_NEWSLETTER_TEMPLATE')) { http_response_code(404); exit; }

/**
 * Tabellenbasiertes, responsives HTML für gängige E-Mail-Clients.
 * Alle redaktionellen Inhalte werden als Text escaped. Keine Trackingpixel oder externen Assets.
 */
function newsletterTemplate(array $campaign, string $unsubscribeUrl, bool $isPreview=false): string {
    $esc=static fn(mixed $s): string => htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $subjects=['turniere'=>'TURNIERE & FREIE PLÄTZE','regeln'=>'REGELWISSEN & TIPPS','workshops'=>'WORKSHOPS & VERANSTALTUNGEN'];
    $category=$esc($subjects[(string)($campaign['topic']??'')]??'TENNIS-SCHIRI NEWSLETTER');
    $subject=trim((string)($campaign['subject']??''));
    $heading=trim((string)($campaign['headline']??'')) ?: $subject;
    $teaser=trim((string)($campaign['preheader']??'')) ?: 'Neues aus der Tennis-Schiri-Welt – direkt in dein Postfach.';
    $heading=$esc($heading);
    $preheader=$esc($teaser);
    $body=(string)($campaign['body']??'');
    $parts=preg_split('/\R{2,}/u', trim($body)) ?: [];
    $paragraphs='';
    foreach($parts as $part){
        $paragraphs.='<p style="font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.85;color:#3d5649;margin:0 0 22px;">'.
            nl2br($esc(trim($part)),false).'</p>';
    }
    $buttonText=trim((string)($campaign['button_label']??''));
    $buttonUrl=trim((string)($campaign['button_url']??''));
    $cta='';
    if($buttonText!=='' && preg_match('~^https://[a-z0-9.-]+(?::443)?(?:[/?#][^\s<>"\x00-\x1f]*)?$~iD',$buttonUrl)){
        $label=$esc($buttonText);
        $link=$esc($buttonUrl);
        $cta=<<<HTML
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 28px"><tr><td bgcolor="#c2ee7b" style="background-color:#c2ee7b;border-radius:9px">
          <a href="$link" target="_blank" style="display:inline-block;padding:16px 24px;border-radius:9px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;text-decoration:none;color:#143b29;background-color:#c2ee7b;">$label &nbsp; &#8599;</a>
        </td></tr></table>
HTML;
    }
    $eventTitle=trim((string)($campaign['event_title']??''));
    $eventDate=trim((string)($campaign['event_date']??''));
    $eventPlace=trim((string)($campaign['event_location']??''));
    $event='';
    if ($eventTitle!=='') {
        $name=$esc($eventTitle);
        $date=$eventDate!==''?'<p style="margin:9px 0 2px;color:#305c43;font-size:13px;font-weight:700;">&#128197; '.$esc($eventDate).'</p>':'';
        $place=$eventPlace!==''?'<p style="margin:4px 0 0;color:#5f7868;font-size:13px;">&#9679; '.$esc($eventPlace).'</p>':'';
        $event=<<<HTML
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f1f7e9;border:1px solid #d9e8c9;border-radius:12px;margin:4px 0 27px">
          <tr><td style="padding:24px 25px;">
            <p style="margin:0 0 9px;font-size:10px;letter-spacing:1.7px;font-weight:800;color:#577f45;">IM FOKUS</p>
            <p style="margin:0;color:#17402c;font-size:23px;line-height:1.25;font-weight:800;">$name</p>
            $date
            $place
          </td></tr>
        </table>
HTML;
    }
    $selectedCards='';
    $selectedData=json_decode((string)($campaign['selected_events']??'[]'),true);
    if(is_array($selectedData) && count($selectedData)>0){
        $cards='';
        foreach(array_slice($selectedData,0,25) as $selected){
            if(!is_array($selected))continue;
            $title=trim((string)($selected['title']??''));
            $url=trim((string)($selected['url']??''));
            if($title===''||!preg_match('~^https://[^[:space:]<>"]+$~iD',$url))continue;
            $name=$esc($title);
            $day=$esc((string)($selected['date']??''));
            $place=$esc((string)($selected['location']??''));
            $kind=($selected['type']??'')==='veranstaltung'?'WORKSHOP / EVENT':'TENNISTURNIER';
            $link=$esc($url);
            $actionText=$esc((string)($selected['link_label']??'Details ansehen'));
            $cards.=<<<HTML
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f2f7ed;border:1px solid #dce9d3;border-radius:12px;margin:0 0 14px">
              <tr><td style="padding:22px 24px;font-family:Arial,Helvetica,sans-serif">
                <p style="margin:0 0 8px;font-size:10px;letter-spacing:1.5px;font-weight:800;color:#5f8746;">$kind</p>
                <p style="font-size:21px;line-height:1.25;color:#16412d;font-weight:800;margin:0 0 11px;">$name</p>
                <p style="font-size:13px;line-height:1.55;color:#3d6248;font-weight:700;margin:0 0 5px;">$day</p>
                <p style="font-size:12px;line-height:1.6;color:#6d8271;margin:0 0 14px;">$place</p>
                <a href="$link" target="_blank" style="color:#1a5b36;font-size:13px;font-weight:800;text-decoration:underline;">$actionText &#8599;</a>
              </td></tr>
            </table>
HTML;
        }
        if($cards!==''){
            $selectedCards=<<<HTML
            <div style="margin:28px 0 31px">
              <p style="font-family:Arial,Helvetica,sans-serif;color:#2b6644;font-size:11px;font-weight:800;letter-spacing:1.6px;margin:0 0 16px;">TERMINE AUF EINEN BLICK</p>
              $cards
            </div>
HTML;
        }
    }
    // Optional compact partner/project banner section. Names and links remain usable if images are blocked.
    $partnerBanners='';
    $selectedPartners=json_decode((string)($campaign['selected_partners']??'[]'),true);
    if(is_array($selectedPartners) && $selectedPartners){
        $bannerRows='';
        foreach(array_slice($selectedPartners,0,12) as $partner){
            if(!is_array($partner))continue;
            $brand=trim((string)($partner['name']??''));
            $target=trim((string)($partner['url']??''));
            $imageUrl=trim((string)($partner['logo']??''));
            if($brand===''||!preg_match('~^https://[^[:space:]<>"]+$~iD',$target))continue;
            $allowImage=preg_match('~^https://tennis-schiri\.de/assets/partner/[a-z0-9-]+\.(?:png|jpg|jpeg|webp)$~D',$imageUrl)
                     || $imageUrl==='https://tennilo.de/assets/tennilo-logo.png';
            $title=$esc($brand);
            $link=$esc($target);
            $kind=(($partner['category']??'')==='projekt')?'UNSER PROJEKT':'UNSER PARTNER';
            $subline=$esc((string)($partner['caption']??''));
            $bg=(($partner['category']??'')==='projekt')?'#eff7f7':'#f7faf4';
            $img=$allowImage?'<img src="'.$esc($imageUrl).'" alt="'.$title.'" width="100" style="display:block;border:0;width:100px;max-width:100%;height:auto;max-height:54px;object-fit:contain;">'
                :'<strong style="font-size:13px;color:#173e2e;">'.$title.'</strong>';
            $bannerRows.=<<<HTML
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 9px;border:1px solid #dce8d9;background:$bg;border-radius:10px">
              <tr>
                <td width="126" valign="middle" style="width:126px;padding:12px 9px 12px 16px;">
                  <a href="$link" target="_blank" style="display:block;text-decoration:none;">$img</a>
                </td>
                <td valign="middle" style="padding:12px 13px 12px 0;font-family:Arial,Helvetica,sans-serif;">
                  <p style="font-size:9px;letter-spacing:1.2px;font-weight:800;color:#5e886a;margin:0 0 4px;">$kind</p>
                  <a href="$link" target="_blank" style="display:block;color:#214a34;font-size:15px;font-weight:800;line-height:1.3;text-decoration:none;">$title &#8599;</a>
                  <p style="font-size:11px;color:#708473;line-height:1.5;margin:3px 0 0;">$subline</p>
                </td>
              </tr>
            </table>
HTML;
        }
        if($bannerRows!==''){
            $partnerBanners=<<<HTML
            <div style="margin:30px 0 2px;">
              <p style="font-family:Arial,Helvetica,sans-serif;color:#477057;font-size:10px;font-weight:800;letter-spacing:1.5px;margin:0 0 13px;">PARTNER &amp; PROJEKTE</p>
              $bannerRows
            </div>
HTML;
        }
    }
    $previewBanner=$isPreview?'<tr><td style="background:#f9e5ac;color:#513d19;text-align:center;font-size:12px;font-weight:bold;padding:12px 20px;font-family:Arial,Helvetica,sans-serif">TEST / VORSCHAU – noch nicht an Abonnenten verschickt</td></tr>':'';
    $unsub=$esc($unsubscribeUrl);
    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light">
<title>$heading | Tennis-Schiri</title>
<style>
 @media screen and (max-width:620px){
   .outer{padding:15px 10px!important}
   .panel{width:100%!important}
   .padded{padding:28px 24px!important}
   .headline{font-size:34px!important;line-height:1.11!important}
   .header-pad{padding:25px 24px!important}
 }
</style>
</head>
<body style="margin:0;padding:0;background:#eef3e8;color:#163b2b;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
<div style="display:none;font-size:1px;line-height:1px;color:#eef3e8;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">$preheader</div>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="outer" style="background:#eef3e8;padding:35px 16px"><tr><td align="center">
 <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="panel" style="width:600px;max-width:600px;background:#ffffff;border-collapse:separate;border-radius:16px;overflow:hidden">
  <tr><td bgcolor="#143b2c" class="header-pad" style="background:#143b2c;padding:31px 42px 30px;">
   <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr>
    <td valign="middle" style="font-family:Arial,Helvetica,sans-serif;color:#ffffff;font-size:22px;letter-spacing:-.8px;font-weight:800;white-space:nowrap;">tennis-schiri<span style="color:#c2ee7b">.de</span></td>
    <td align="right" valign="middle" style="font-family:Arial,Helvetica,sans-serif;font-size:10px;letter-spacing:2px;font-weight:bold;color:#c2ee7b;">NEWSLETTER</td>
   </tr></table>
  </td></tr>
  <tr><td height="5" bgcolor="#c2ee7b" style="font-size:1px;line-height:1px;background:#c2ee7b;height:5px;">&nbsp;</td></tr>
  $previewBanner
  <tr><td class="padded" style="padding:43px 43px 34px;">
   <p style="color:#45845c;font-size:11px;font-weight:700;letter-spacing:2px;line-height:1.4;margin:0 0 15px;">$category</p>
   <h1 class="headline" style="font-family:Arial,Helvetica,sans-serif;font-size:43px;line-height:1.08;letter-spacing:-1.7px;color:#163b2b;margin:0 0 24px;font-weight:800;">$heading</h1>
   <div style="height:1px;background:#dfebd7;margin:0 0 30px"></div>
   $paragraphs
   $event
   $selectedCards
   $cta
   $partnerBanners
  </td></tr>
  <tr><td class="padded" style="background:#f5f9f0;padding:25px 43px;border-top:1px solid #e3eddb">
   <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;color:#22533b;margin:0 0 7px">Mehr Tennis. Weniger Suchen.</p>
   <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#597465;margin:0">Aktuelle Turniere, Regeln und Veranstaltungen findest du auch auf <a href="https://tennis-schiri.de/" style="color:#285d3e;text-decoration:underline">tennis-schiri.de</a>.</p>
  </td></tr>
 </table>
 <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="panel" style="width:600px;max-width:600px;margin-top:22px">
  <tr><td style="padding:0 25px;text-align:center;font-family:Arial,Helvetica,sans-serif;color:#6b8173;font-size:11px;line-height:1.8;">
   Tennis-Schiri &middot; Michael Wiedeck &middot; Neuenhagen bei Berlin<br>
   Du erhältst diese Nachricht, weil du diesen Themenbereich ausdrücklich abonniert hast.<br>
   <a href="$unsub" style="color:#2d6047;text-decoration:underline">Newsletter abbestellen</a>
   &nbsp;&middot;&nbsp;
   <a href="https://tennis-schiri.de/datenschutz/" style="color:#2d6047;text-decoration:underline">Datenschutz</a>
   &nbsp;&middot;&nbsp;
   <a href="https://tennis-schiri.de/impressum/" style="color:#2d6047;text-decoration:underline">Impressum</a>
   <br>Kontakt: <a href="mailto:news@tennis-schiri.de" style="color:#2d6047">news@tennis-schiri.de</a>
  </td></tr>
 </table>
</td></tr></table>
</body>
</html>
HTML;
}

/* Textalternative für Clients ohne HTML-Darstellung. */
function newsletterPlainText(array $campaign,string $unsubscribeUrl): string {
    $title=trim((string)($campaign['headline']??'')) ?: trim((string)($campaign['subject']??''));
    $body=trim((string)($campaign['body']??''));
    $text="TENNIS-SCHIRI NEWSLETTER\n\n".$title."\n\n".$body;
    $eventTitle=trim((string)($campaign['event_title']??''));
    if($eventTitle!==''){
        $text.="\n\nIM FOKUS: ".$eventTitle;
        foreach (['event_date'=>'Termin','event_location'=>'Ort'] as $field=>$label){
            $value=trim((string)($campaign[$field]??''));
            if($value!=='')$text.="\n".$label.": ".$value;
        }
    }
    $selectedData=json_decode((string)($campaign['selected_events']??'[]'),true);
    if(is_array($selectedData) && $selectedData){
        $text.="\n\nTERMINE AUF EINEN BLICK";
        foreach(array_slice($selectedData,0,25) as $row){
            if(!is_array($row) || empty($row['title']) || !preg_match('~^https://[^[:space:]<>"]+$~iD',(string)($row['url']??'')))continue;
            $text.="\n\n".(string)$row['title'];
            if(!empty($row['date']))$text.="\nTermin: ".(string)$row['date'];
            if(!empty($row['location']))$text.="\nOrt: ".(string)$row['location'];
            $text.="\n".(string)($row['link_label']??'Details ansehen').": ".(string)$row['url'];
        }
    }
    $selectedPartners=json_decode((string)($campaign['selected_partners']??'[]'),true);
    if(is_array($selectedPartners) && $selectedPartners){
        $lines=[];
        foreach(array_slice($selectedPartners,0,12) as $partner){
            if(!is_array($partner)||empty($partner['name'])||
               !preg_match('~^https://[^[:space:]<>"]+$~iD',(string)($partner['url']??'')))continue;
            $lines[]=(string)$partner['name'].": ".(string)$partner['url'];
        }
        if($lines)$text.="\n\nPARTNER & PROJEKTE\n".implode("\n",$lines);
    }
    $buttonText=trim((string)($campaign['button_label']??''));
    $buttonUrl=trim((string)($campaign['button_url']??''));
    if($buttonText!=='' && preg_match('~^https://[a-z0-9.-]+(?::443)?(?:[/?#][^\s<>"\x00-\x1f]*)?$~iD',$buttonUrl)){
        $text.="\n\n".$buttonText.": ".$buttonUrl;
    }
    $text.="\n\n---\nTennis-Schiri | tennis-schiri.de\nDu erhältst diese Nachricht aufgrund deiner Newsletter-Einwilligung.\nAbmelden: ".$unsubscribeUrl."\nDatenschutz: https://tennis-schiri.de/datenschutz/\nImpressum: https://tennis-schiri.de/impressum/\nKontakt: news@tennis-schiri.de\n";
    return $text;
}
