<?php
declare(strict_types=1);
define('TS_BACKOFFICE',true);
require_once __DIR__.'/lib.php';
[$cfg,$db]=boStart();
$venues=boVenueList($db);
$tournaments=boTournaments();
$templates=boTemplates();
$method=$_SERVER['REQUEST_METHOD']??'GET';
$view=(string)($_GET['view']??'dashboard');
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;

if($method==='POST'){
 boCheckPost();
 $action=boPost('action',50);
 $tournament=boPost('tournament',100);
 $now=time();
 if($action==='venue_save'){
  $venueId=boPost('venue_id',80);
  if(!isset($venues[$venueId]))boError('Unbekannte Standortvorlage.');
  try{$data=boVenueValidate($_POST);}
  catch(InvalidArgumentException $ex){boError($ex->getMessage());}
  $q=$db->prepare('UPDATE bo_venues SET data=?,updated_at=? WHERE id=?');
  $q->execute([json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$now,$venueId]);
  header('Location: /admin/?view=venues&venue='.rawurlencode($venueId).'&info='.rawurlencode('Standortvorlage gespeichert. Bestehende E-Mail-Entwürfe wurden nicht verändert.'),true,303);exit;
 }
 if($action==='contact_add'){
  if(!isset($tournaments[$tournament]))boError('Bitte ein verfügbares Turnier auswählen.');
  $email=strtolower(boPost('email',254));$name=boPost('name',160);
  if(!boSafeEmail($email)||strlen($name)>160)boError('Bitte eine gültige E-Mail-Adresse und einen Namen mit maximal 160 Zeichen angeben.');
  $q=$db->prepare('INSERT INTO bo_contacts(tournament_id,name,email,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(tournament_id,email) DO UPDATE SET name=excluded.name,updated_at=excluded.updated_at');
  $q->execute([$tournament,$name,$email,$now,$now]);
  boGoContacts($tournament,'Teilnehmerkontakt gespeichert.');
 }
 if($action==='contact_delete'){
  $contactId=filter_var($_POST['contact_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
  $check=$db->prepare('SELECT id FROM bo_contacts WHERE id=? AND tournament_id=?');$check->execute([$contactId,$tournament]);
  if(!$check->fetch())boError('Teilnehmerkontakt für dieses Turnier nicht gefunden.',404);
  $db->beginTransaction();
  try{
   // Widerruf / Löschung: keine neuen Mails mehr; personenbezogene Kopien in Versandprotokollen anonymisieren.
   $q=$db->prepare("UPDATE bo_deliveries SET name='',email='',state=CASE WHEN state='pending' THEN 'skipped' ELSE state END WHERE contact_id=?");
   $q->execute([$contactId]);
   $q=$db->prepare('DELETE FROM bo_contacts WHERE id=? AND tournament_id=?');$q->execute([$contactId,$tournament]);
   $db->commit();
  }catch(Throwable $ex){if($db->inTransaction())$db->rollBack();boError('Teilnehmer konnte nicht entfernt werden.',503);}
  boGoContacts($tournament,'Teilnehmerkontakt entfernt. Vorhandene Protokolle wurden anonymisiert und offene Sendungen übersprungen.');
 }
 if($action==='contact_import'){
  if(!isset($tournaments[$tournament]))boError('Bitte zuerst ein Turnier auswählen.');
  $upload=$_FILES['csv']??null;
  if(!is_array($upload)||($upload['error']??1)!==UPLOAD_ERR_OK||($upload['size']??0)>1024*1024||
    ($upload['size']??0)<5||!is_uploaded_file((string)($upload['tmp_name']??''))){
    boError('Bitte eine CSV-Datei bis 1 MB hochladen. Die Datei wird nicht dauerhaft auf dem Server gespeichert.');
  }
  $file=fopen((string)$upload['tmp_name'],'rb');
  if(!$file)boError('Datei konnte nicht gelesen werden.');
  $line=fgets($file,4096);
  if($line===false){fclose($file);boError('Die CSV-Datei ist leer.');}
  $separator=count(str_getcsv($line,';'))>=count(str_getcsv($line,','))?';':',';
  rewind($file);
  $headers=fgetcsv($file,4096,$separator);
  if(!is_array($headers)){fclose($file);boError('CSV-Überschriften nicht lesbar.');}
  $headers=array_map(static function($v) {
    $clean=strtolower(trim(preg_replace('/^\xEF\xBB\xBF/','',(string)$v)));
    return strtr($clean,['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss']);
  },$headers);
  $mailColumn=null;$nameColumn=null;$firstColumn=null;$lastColumn=null;
  foreach($headers as $k=>$header){
    if($mailColumn===null&&in_array($header,['e-mail','email','e-mail-adresse','emailadresse','mail','mailadresse','e_mail'],true))$mailColumn=$k;
    if($nameColumn===null&&in_array($header,['name','teilnehmer','teilnehmername','spieler','spielername','vollstaendiger name'],true))$nameColumn=$k;
    if($firstColumn===null&&in_array($header,['vorname','first name','firstname'],true))$firstColumn=$k;
    if($lastColumn===null&&in_array($header,['nachname','last name','lastname'],true))$lastColumn=$k;
  }
  if($mailColumn===null){fclose($file);boError('Die CSV-Datei braucht eine Spalte „E-Mail“ oder „Email“. Unterstützt werden zusätzlich „Name“ oder „Vorname“/„Nachname“.');}
  $import=[];$invalid=0;$lines=0;
  while(($values=fgetcsv($file,65536,$separator))!==false){
    ++$lines;
    if($lines>500){fclose($file);boError('Maximal 500 CSV-Zeilen pro Import. Bitte die Datei teilen.');}
    if(count($values)===1&&trim((string)$values[0])==='')continue;
    $email=strtolower(trim((string)($values[$mailColumn]??'')));
    $name=$nameColumn!==null?trim((string)($values[$nameColumn]??'')):trim((string)($values[$firstColumn??-1]??'').' '.(string)($values[$lastColumn??-1]??''));
    if(!boSafeEmail($email)||strlen($name)>160){++$invalid;continue;}
    $import[$email]=['name'=>$name,'email'=>$email];
  }
  fclose($file);
  if(!$import)boError('Keine gültigen E-Mail-Adressen gefunden. Bitte CSV-Datei prüfen.');
  $db->beginTransaction();
  try{
   $q=$db->prepare('INSERT INTO bo_contacts(tournament_id,name,email,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(tournament_id,email) DO UPDATE SET name=excluded.name,updated_at=excluded.updated_at');
   foreach($import as $p)$q->execute([$tournament,$p['name'],$p['email'],$now,$now]);
   $db->commit();
  }catch(Throwable $e){if($db->inTransaction())$db->rollBack();error_log('BO import: '.$e->getMessage());boError('Der CSV-Import konnte nicht gespeichert werden.',503);}
  boGoContacts($tournament,count($import).' Teilnehmeradressen übernommen. '.$invalid.' ungültige Zeilen übersprungen. Kein Versand ausgelöst.');
 }
 if($action==='campaign_create'){
  $type=boPost('template_type',30);
  if(!isset($templates[$type])||!isset($tournaments[$tournament]))boError('Bitte eine gültige Vorlage und ein Turnier auswählen.');
  $t=$tournaments[$tournament];$template=$templates[$type];
  $venueId=boPost('venue_id',80);
  if(!isset($venues[$venueId]))boError('Bitte eine gültige Standortvorlage wählen.');
  $q=$db->prepare("INSERT INTO bo_campaigns(tournament_id,tournament_name,tournament_date,tournament_location,tournament_url,template_type,subject,body,notes,venue_id,venue_data,state,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,'draft',?,?)");
  $q->execute([$t['id'],$t['title'],$t['date'],$t['location'],$t['url'],$type,$template['subject'],$template['body'],'',$venueId,$venues[$venueId]['data'],$now,$now]);
  boGo('campaign',(int)$db->lastInsertId(),'Entwurf erstellt. Bitte Text prüfen und bei Bedarf anpassen.');
 }
 $campaignId=filter_var($_POST['campaign_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
 $campaign=$campaignId>0?boCampaign($db,$campaignId):null;
 if(!$campaign)boError('Kampagne nicht gefunden.',404);
 if($action==='campaign_save'){
  if($campaign['state']!=='draft')boError('Nur Entwürfe können geändert werden.');
  $subject=boPost('subject',160);
  $body=boPost('body',15000);
  $notes=boPost('notes',3000);
  try{$info=boInfoFromPost($_POST);}
  catch(InvalidArgumentException $ex){boError($ex->getMessage());}

  if($subject===''||strlen($subject)>160||preg_match('/[\r\n]/',$subject)||$body===''||strlen($body)>15000||strlen($notes)>3000)boError('Bitte Betreff (max. 160 Zeichen) und Nachrichtentext (max. 15.000 Zeichen) prüfen.');
  $q=$db->prepare("UPDATE bo_campaigns SET subject=?,body=?,notes=?,info_sections=?,info_arrival=?,info_schedule=?,info_parking=?,info_fees=?,turnigo_url=?,myscore_url=?,info_contact=?,updated_at=? WHERE id=? AND state='draft'");
  $q->execute([$subject,$body,$notes,$info['info_sections'],$info['info_arrival'],$info['info_schedule'],$info['info_parking'],$info['info_fees'],$info['turnigo_url'],$info['myscore_url'],$info['info_contact'],$now,$campaignId]);
  boGo('campaign',$campaignId,'Entwurf gespeichert.');
 }
 if($action==='campaign_test'){
  if($campaign['state']!=='draft')boError('Testmails nur vor der Versandfreigabe möglich.');
  [$subject,$text,$html]=boMailContent($campaign,['name'=>'Alex Beispiel','email'=>'mw@tennis-schiri.de'],true);
  $sent=boSend('mw@tennis-schiri.de','[TEST] '.$subject,$text,$html);
  boGo('campaign',$campaignId,$sent?'Testmail vom Mailserver angenommen (Empfänger: mw@tennis-schiri.de).':'Testmail vom Mailserver nicht angenommen.');
 }
 if($action==='campaign_approve'){
  if($campaign['state']!=='draft'||boPost('confirm',20)!=='SENDEN')boError('Für die Freigabe muss der Entwurf offen sein und SENDEN eingegeben werden.');
  $db->exec('BEGIN IMMEDIATE TRANSACTION');
  try{
   $check=boCampaign($db,$campaignId);
   if(!$check||$check['state']!=='draft')throw new RuntimeException('Kampagne wurde bereits freigegeben.');
   $q=$db->prepare('SELECT id,email,name FROM bo_contacts WHERE tournament_id=? ORDER BY id');
   $q->execute([$check['tournament_id']]);$all=$q->fetchAll();
   if(!$all)throw new RuntimeException('Für das Turnier sind noch keine Teilnehmer eingetragen.');
   if(count($all)>500)throw new RuntimeException('Mehr als 500 Empfänger – bitte Teilnehmerliste prüfen.');
   $insert=$db->prepare("INSERT INTO bo_deliveries(campaign_id,contact_id,email,name,state) VALUES(?,?,?,?,'pending')");
   foreach($all as $contact)$insert->execute([$campaignId,$contact['id'],$contact['email'],$contact['name']]);
   $update=$db->prepare("UPDATE bo_campaigns SET state='approved',approved_at=?,updated_at=? WHERE id=? AND state='draft'");
   $update->execute([$now,$now,$campaignId]);
   $db->commit();
  }catch(Throwable $e){if($db->inTransaction())$db->rollBack();boError($e->getMessage());}
  boGo('campaign',$campaignId,count($all).' Empfänger festgelegt. Es wurde noch keine Mail versendet.');
 }
 if($action==='campaign_send'){
  if(!in_array($campaign['state'],['approved','sending'],true))boError('Die Kampagne ist nicht für den Versand freigegeben.');
  $outcome=['sent'=>0,'failed'=>0,'skipped'=>0];
  for($i=0;$i<5;$i++){
   $db->exec('BEGIN IMMEDIATE TRANSACTION');
   try{
    $q=$db->prepare("SELECT * FROM bo_deliveries WHERE campaign_id=? AND state='pending' ORDER BY contact_id LIMIT 1");
    $q->execute([$campaignId]);
    $record=$q->fetch();
    if(!$record){$db->commit();break;}
    // Reservierung VOR mail(): nach einem Absturz kein unbemerkter Doppelversand.
    $reserve=$db->prepare("UPDATE bo_deliveries SET state='attempted',attempted_at=? WHERE campaign_id=? AND contact_id=? AND state='pending'");
    $reserve->execute([$now,$campaignId,$record['contact_id']]);
    $db->prepare("UPDATE bo_campaigns SET state='sending',updated_at=? WHERE id=?")->execute([$now,$campaignId]);
    $db->commit();
   }catch(Throwable $e){if($db->inTransaction())$db->rollBack();error_log('BO queue: '.$e->getMessage());boError('Versandwarteschlange momentan nicht verfügbar.',503);}
   $q=$db->prepare('SELECT email FROM bo_contacts WHERE id=? AND tournament_id=?');
   $q->execute([$record['contact_id'],$campaign['tournament_id']]);
   $current=$q->fetchColumn();
   if($current===false||strcasecmp((string)$current,(string)$record['email'])!==0){
    $state='skipped';
   }else{
    [$subject,$text,$html]=boMailContent($campaign,['name'=>$record['name'],'email'=>$record['email']],false);
    $ok=boSend($record['email'],$subject,$text,$html);
    $state=$ok?'sent':'failed';
   }
   $u=$db->prepare("UPDATE bo_deliveries SET state=? WHERE campaign_id=? AND contact_id=? AND state='attempted'");
   $u->execute([$state,$campaignId,$record['contact_id']]);
   ++$outcome[$state];
  }
  $q=$db->prepare("SELECT COUNT(*) FROM bo_deliveries WHERE campaign_id=? AND state='pending'");$q->execute([$campaignId]);
  $left=(int)$q->fetchColumn();
  if($left===0)$db->prepare("UPDATE bo_campaigns SET state='done',updated_at=? WHERE id=?")->execute([time(),$campaignId]);
  boGo('campaign',$campaignId,$outcome['sent'].' vom Mailserver angenommen, '.$outcome['failed'].' fehlgeschlagen, '.$outcome['skipped'].' übersprungen. '.$left.' weitere offen.');
 }
 boError('Unbekannte Aktion.',400);
}

if($view==='preview'&&$id){
 $campaign=boCampaign($db,$id);
 if(!$campaign)boError('Entwurf nicht gefunden.',404);
 header('Content-Type: text/html; charset=UTF-8');
 [,,$html]=boMailContent($campaign,['name'=>'Alex Beispiel','email'=>'mw@tennis-schiri.de'],true);
 echo $html;exit;
}

$e=static fn($x)=>boEsc($x);
$labels=['draft'=>'gray','approved'=>'orange','sending'=>'orange','done'=>''];
if($view==='dashboard'){
 $newsletterConfirmed=(int)$db->query('SELECT COUNT(*) FROM subscribers WHERE confirmed_at IS NOT NULL')->fetchColumn();
 $contacts=(int)$db->query('SELECT COUNT(*) FROM bo_contacts')->fetchColumn();
 $drafts=(int)$db->query("SELECT COUNT(*) FROM bo_campaigns WHERE state='draft'")->fetchColumn();
 $pending=(int)$db->query("SELECT COUNT(*) FROM bo_deliveries WHERE state='pending'")->fetchColumn();
 $stats=[
  ['Newsletter-Abonnenten',$newsletterConfirmed,'/newsletter-admin.php'],
  ['Turnierkontakte',$contacts,boUrl('contacts')],
  ['Infomail-Entwürfe',$drafts,boUrl('compose')],
  ['Freigegebene, noch offene Mails',$pending,boUrl('history')],
 ];
 $html='<div class="grid">';
 foreach($stats as $stat)$html.='<div class="tile"><div class="stat">'.$stat[1].'</div><h3>'.$e($stat[0]).'</h3><a href="'.$e($stat[2]).'">Öffnen ↗</a></div>';
 $html.='</div><div class="split" style="margin-top:18px"><section class="panel"><h2>Newsletter</h2><p>Bewährte Newsletter-Verwaltung mit Double-Opt-in, Themenauswahl, Turnieren und Partnerbannern.</p><p><a class="button" href="/newsletter-mail.php">Newsletter erstellen</a></p><a href="/newsletter-admin.php">Abonnenten ansehen</a></section><section class="panel"><h2>Turnier-Infomails</h2><p>Turnier wählen, Teilnehmer importieren, E-Mail-Vorlage bearbeiten und nach Testlauf manuell versenden.</p><p><a class="button" href="'.boUrl('compose').'">Infomail vorbereiten</a></p><a href="'.boUrl('contacts').'">Teilnehmer verwalten</a><p class="help">Bisherige Turnier-Mail-Entwürfe aus dem älteren System bleiben unter <a href="/turnier-mails.php">Turnier-Mails (bisherige Verwaltung)</a> erreichbar. Sie werden nicht ungefragt verschoben.</p></section></div>';
 $html.='<section class="panel"><h2>Zuletzt bearbeitete Infomails</h2>';
 $recent=$db->query('SELECT * FROM bo_campaigns ORDER BY updated_at DESC LIMIT 6')->fetchAll();
 if(!$recent)$html.='<p class="muted">Noch keine Infomails angelegt.</p>';
 else{
  $html.='<div class="tablewrap"><table><thead><tr><th>Turnier</th><th>Betreff</th><th>Status</th><th>Letzte Änderung</th></tr></thead><tbody>';
  foreach($recent as $r)$html.='<tr><td>'.$e($r['tournament_name']).'</td><td><a href="'.boUrl('campaign',(int)$r['id']).'">'.$e($r['subject']).'</a></td><td><span class="badge '.($labels[$r['state']]??'').'">'.$e(boStatus($r['state'])).'</span></td><td>'.date('d.m.Y H:i',(int)$r['updated_at']).'</td></tr>';
  $html.='</tbody></table></div>';
 }
 $html.='</section><p class="help">Es gibt keine automatische Veröffentlichung und keinen automatischen E-Mail-Versand. Die Website selbst wird weiterhin über den gewohnten GitHub-Live-Upload gepflegt.</p>';
 boPage('Übersicht',$html);
}

if($view==='contacts'){
 $selected=(string)($_GET['tournament']??'');
 if(!isset($tournaments[$selected]))$selected=array_key_first($tournaments)??'';
 $selection=boTournamentSelect($tournaments,$selected);
 $html='<section class="panel"><h2>Teilnehmer je Turnier</h2><p>Organisatorische Teilnehmerkontakte – ausdrücklich <strong>kein Newsletter-Verteiler</strong>. Du kannst eine CSV-Datei importieren oder einzelne Kontakte ergänzen. Kein Import löst automatisch E-Mails aus.</p>';
 if(!$tournaments)$html.='<p class="warning">Die Terminliste ist noch nicht verfügbar. Bitte zuerst den GitHub-Live-Upload ausführen.</p>';
 $html.='<form method="get"><input type="hidden" name="view" value="contacts"><label>Turnier wählen</label><div class="row">'.$selection.'<button class="button-secondary" type="submit">Anzeigen</button></div></form></section>';
 if($selected!==''){
  $q=$db->prepare('SELECT * FROM bo_contacts WHERE tournament_id=? ORDER BY name,email LIMIT 501');$q->execute([$selected]);$rows=$q->fetchAll();
  $html.='<div class="split"><section class="panel"><h2>CSV-Teilnehmerliste importieren</h2><p class="help">CSV mit Überschriften „E-Mail“ und optional „Name“ oder „Vorname“/„Nachname“. Trennzeichen Semikolon oder Komma. Maximal 500 Zeilen / 1 MB. Doppelte E-Mail-Adressen werden je Turnier zusammengeführt.</p><form method="post" enctype="multipart/form-data">'.boCsrf().'<input type="hidden" name="action" value="contact_import"><input type="hidden" name="tournament" value="'.$e($selected).'"><label>CSV-Datei</label><input type="file" name="csv" accept=".csv,text/csv,text/plain" required><button type="submit">Kontakte importieren</button></form></section>';
  $html.='<section class="panel"><h2>Teilnehmer einzeln ergänzen</h2><form method="post">'.boCsrf().'<input type="hidden" name="action" value="contact_add"><input type="hidden" name="tournament" value="'.$e($selected).'"><label>Vor- und Nachname</label><input type="text" name="name" maxlength="160" placeholder="Optional"><label>E-Mail-Adresse</label><input type="email" name="email" maxlength="254" required><button type="submit">Teilnehmer speichern</button></form></section></div>';
  $html.='<section class="panel"><div class="row row-between"><h2>Teilnehmerliste · '.count($rows).' Einträge</h2><a class="button button-secondary" href="'.boUrl('compose').'">Infomail vorbereiten</a></div>';
  if(!$rows)$html.='<p class="muted">Für dieses Turnier sind noch keine Teilnehmer hinterlegt.</p>';
  else{
   $html.='<div class="tablewrap"><table><thead><tr><th>Name</th><th>E-Mail</th><th>Aktion</th></tr></thead><tbody>';
   foreach($rows as $r){
    $html.='<tr><td>'.$e($r['name']?:'–').'</td><td>'.$e($r['email']).'</td><td><form class="inline" method="post">'.boCsrf().'<input type="hidden" name="action" value="contact_delete"><input type="hidden" name="tournament" value="'.$e($selected).'"><input type="hidden" name="contact_id" value="'.(int)$r['id'].'"><button class="button-secondary" type="submit">Entfernen</button></form></td></tr>';
   }
   $html.='</tbody></table></div>';
  }
  $html.='</section>';
 }
 boPage('Teilnehmerverwaltung',$html);
}

if($view==='venues'){
 $chosen=(string)($_GET['venue']??($_GET['venue_id']??'general'));
 if(!isset($venues[$chosen]))$chosen='general';
 $profile=$venues[$chosen];
 $html='<section class="panel"><h2>Wiederverwendbare Standortvorlagen</h2><p>Hier hinterlegst du Anschrift, Belag, Anreise, Gastronomie und Turnierleitung <strong>einmal je Standort</strong>. Bitte nur aktuell bestätigte Angaben speichern. Alte Preise und Ausstattungsmerkmale werden nicht ungefragt vorausgefüllt.</p><form method="get"><input type="hidden" name="view" value="venues"><label>Standort auswählen</label>'.boVenueSelector($venues,$chosen).'<button type="submit" class="button-secondary">Vorlage öffnen</button></form></section>';
 // The selector above posts the name venue_id; accept this as well as bookmarked ?venue=.
 $html.='<section class="panel"><h2>'.boEsc($profile['name']).'</h2><form method="post">'.boCsrf().'<input type="hidden" name="action" value="venue_save"><input type="hidden" name="venue_id" value="'.boEsc($chosen).'">'.boVenueEditor($profile).'<p><button type="submit">Standortvorlage speichern</button></p></form></section>';
 $html.='<p class="help">Jede neu erstellte Infomail übernimmt einen <strong>Snapshot</strong>. Selbst wenn die Standortvorlage nachträglich geändert wird, bleiben ältere Entwürfe und bereits freigegebene Mails unverändert.</p>';
 boPage('Standortvorlagen',$html);
}

if($view==='templates'){
 $html='<section class="panel"><h2>Vorlagen für organisatorische Turniermails</h2><p>Die Texte dienen als Ausgangspunkt. Betreff und Nachricht kannst du in jedem Entwurf individuell bearbeiten. Platzhalter werden beim Versand mit dem Turnier und dem jeweiligen Teilnehmer ausgefüllt.</p><p><code>{{name}}</code> · <code>{{turnier}}</code> · <code>{{datum}}</code> · <code>{{ort}}</code> · <code>{{turnier_link}}</code> · <code>{{zusatz}}</code></p></section><div class="grid">';
 foreach($templates as $type=>$t){
  $html.='<div class="tile"><h3>'.$e($t['label']).'</h3><p><strong>Betreff:</strong> '.$e($t['subject']).'</p><p class="help">'.nl2br($e(substr($t['body'],0,245))).'…</p><a href="'.boUrl('compose').'&amp;template='.$e($type).'">Mit dieser Vorlage starten ↗</a></div>';
 }
 $html.='</div><p class="help">Die Vorlagen enthalten bewusst keine Werbung. Newsletter und Turnierorganisation bleiben rechtlich und technisch getrennt.</p>';
 boPage('E-Mail-Vorlagen',$html);
}

if($view==='compose'){
 $html='<section class="panel"><h2>Neue Turnier-Infomail</h2><p>Mit Turnierdaten aus der Website und einer bearbeitbaren Vorlage. Du wählst den Empfängerkreis später über die Teilnehmerliste des jeweiligen Turniers.</p>';
 if(!$tournaments)$html.='<p class="warning">Der Terminimport muss zuerst durch den Live-Upload bereitgestellt werden.</p>';
 else{
  $html.='<form method="post">'.boCsrf().'<input type="hidden" name="action" value="campaign_create"><label>Turnier</label>'.boTournamentSelect($tournaments).'<label>E-Mail-Vorlage</label><select name="template_type">';
  foreach($templates as $type=>$t)$html.='<option value="'.$e($type).'"'.(($_GET['template']??'')===$type?' selected':'').'>'.$e($t['label']).'</option>';
  $html.='</select><label>Standortvorlage</label>'.boVenueSelector($venues).'<p class="help">Wiederverwendbare Anschrift, Navigation und Hinweise. Die Angaben werden bei Erstellung im Entwurf gespeichert und später nicht unbemerkt aktualisiert. <a href="'.boUrl('venues').'">Vorlagen bearbeiten</a></p><button type="submit">Entwurf anlegen</button></form>';
 }
 $html.='</section><section class="panel"><h2>Alle Entwürfe und Aussendungen</h2>';
 $rows=$db->query('SELECT * FROM bo_campaigns ORDER BY created_at DESC LIMIT 100')->fetchAll();
 if(!$rows)$html.='<p class="muted">Es gibt noch keine Turnier-Infomails.</p>';
 else{
  $html.='<div class="tablewrap"><table><thead><tr><th>Turnier</th><th>Betreff</th><th>Status</th><th>Angelegt</th></tr></thead><tbody>';
  foreach($rows as $r){
   $html.='<tr><td>'.$e($r['tournament_name']).'</td><td><a href="'.boUrl('campaign',(int)$r['id']).'">'.$e($r['subject']).'</a></td><td><span class="badge '.($labels[$r['state']]??'').'">'.$e(boStatus($r['state'])).'</span></td><td>'.date('d.m.Y',(int)$r['created_at']).'</td></tr>';
  }
  $html.='</tbody></table></div>';
 }
 $html.='</section>';
 boPage('Turnier-Infomails',$html);
}

if($view==='campaign'&&$id){
 $c=boCampaign($db,$id);
 if(!$c)boError('Infomail nicht gefunden.',404);
 $counts=[];
 $q=$db->prepare('SELECT state,COUNT(*) as total FROM bo_deliveries WHERE campaign_id=? GROUP BY state');$q->execute([$id]);
 foreach($q->fetchAll() as $rec)$counts[$rec['state']]=(int)$rec['total'];
 $q=$db->prepare('SELECT COUNT(*) FROM bo_contacts WHERE tournament_id=?');$q->execute([$c['tournament_id']]);
 $total=(int)$q->fetchColumn();
 $html='<section class="panel"><div class="row row-between"><div><p class="help">Standortvorlage: '.$e($venues[$c['venue_id']??'general']['name']??'Allgemein').'</p><h2>'.$e($c['tournament_name']).'</h2><p>'.$e($c['tournament_date']).' · '.$e($c['tournament_location']).'</p><a href="'.$e($c['tournament_url']).'" target="_blank" rel="noopener noreferrer">Turnierseite öffnen ↗</a></div><span class="badge '.($labels[$c['state']]??'').'">'.$e(boStatus($c['state'])).'</span></div></section>';
 if($c['state']==='draft'){
  $html.='<section class="panel"><h2>Betreff und Text bearbeiten</h2><form method="post">'.boCsrf().'<input type="hidden" name="action" value="campaign_save"><input type="hidden" name="campaign_id" value="'.$id.'"><label>Betreff</label><input type="text" name="subject" maxlength="160" required value="'.$e($c['subject']).'"><label>Nachricht</label><textarea name="body" maxlength="15000" rows="15" required>'.$e($c['body']).'</textarea><label>Zusätzliche Hinweise (optional)</label><textarea name="notes" maxlength="3000" rows="5" placeholder="Treffpunkt, Meldezeit, Parkmöglichkeiten, Hinweise für den Turniertag">'.$e($c['notes']??'').'</textarea>'.boInfoEditor($c).'<p class="help">Die Platzhalter {{name}}, {{turnier}}, {{datum}}, {{ort}}, {{turnier_link}} und {{zusatz}} werden beim Versand individuell ersetzt. {{zusatz}} übernimmt das Feld „Zusätzliche Hinweise“. Für Besonderheiten kannst du den Text direkt ändern.</p><button type="submit">Entwurf speichern</button></form></section>';
  $html.='<section class="panel"><h2>Vorschau und Testmail</h2><p>Vorschau mit „Alex Beispiel“ als Musterempfänger. Vor jeder Freigabe Änderungen bitte zuerst speichern.</p><div class="row"><a class="button button-secondary" href="'.boUrl('preview',$id).'" target="_blank" rel="noopener">HTML-Vorschau öffnen</a><form class="inline" method="post">'.boCsrf().'<input type="hidden" name="action" value="campaign_test"><input type="hidden" name="campaign_id" value="'.$id.'"><button type="submit">Testmail an mw@tennis-schiri.de</button></form></div></section>';
  $html.='<section class="panel"><h2>Empfängerliste freigeben</h2><p><strong>'.$total.'</strong> Kontakte für dieses Turnier. Die Freigabe speichert genau diese Empfängerliste. Es werden dabei noch keine E-Mails verschickt.</p>';
  if($total===0)$html.='<p class="warning">Zuerst Teilnehmer unter <a href="'.boUrl('contacts').'&amp;tournament='.$e($c['tournament_id']).'">Teilnehmerverwaltung</a> ergänzen.</p>';
  else $html.='<form method="post">'.boCsrf().'<input type="hidden" name="action" value="campaign_approve"><input type="hidden" name="campaign_id" value="'.$id.'"><label>Zur Freigabe SENDEN eingeben</label><input type="text" name="confirm" maxlength="20" placeholder="SENDEN" autocomplete="off" required><button type="submit">Empfängerliste freigeben</button></form>';
  $html.='</section>';
 }else{
  $pending=$counts['pending']??0;
  $html.='<section class="panel"><h2>Versandstatus</h2><div class="grid">';
  foreach(['pending','attempted','sent','failed','skipped'] as $state) {
   $html.='<div class="tile"><div class="stat">'.(int)($counts[$state]??0).'</div><h3>'.$e(boStatus($state)).'</h3></div>';
  }
  $html.='</div><p class="help">„Vom Mailserver angenommen“ ist kein Zustellnachweis. Ein unterbrochener Versand wird nicht automatisch erneut verschickt.</p>';
  if(in_array($c['state'],['approved','sending'],true)&&$pending>0){
   $html.='<form method="post" style="margin-top:18px">'.boCsrf().'<input type="hidden" name="action" value="campaign_send"><input type="hidden" name="campaign_id" value="'.$id.'"><button type="submit">Nächste bis zu 5 E-Mails senden</button></form>';
  }
  $html.='</section>';
  $q=$db->prepare('SELECT name,email,state,attempted_at FROM bo_deliveries WHERE campaign_id=? ORDER BY contact_id LIMIT 500');$q->execute([$id]);
  $html.='<section class="panel"><h2>Einzelprotokoll</h2><div class="tablewrap"><table><thead><tr><th>Empfänger</th><th>E-Mail</th><th>Status</th><th>Versuch</th></tr></thead><tbody>';
  foreach($q->fetchAll() as $entry){
   $html.='<tr><td>'.$e($entry['name']?:'–').'</td><td>'.$e($entry['email']).'</td><td>'.$e(boStatus($entry['state'])).'</td><td>'.($entry['attempted_at']?date('d.m.Y H:i',(int)$entry['attempted_at']):'–').'</td></tr>';
  }
  $html.='</tbody></table></div></section>';
 }
 boPage('Infomail #'.$id,$html);
}

if($view==='history'){
 $q=$db->query("SELECT c.*,COUNT(d.contact_id) AS total,
 SUM(CASE WHEN d.state='sent' THEN 1 ELSE 0 END) AS accepted,
 SUM(CASE WHEN d.state='failed' THEN 1 ELSE 0 END) AS failed,
 SUM(CASE WHEN d.state='pending' THEN 1 ELSE 0 END) AS pending
 FROM bo_campaigns c LEFT JOIN bo_deliveries d ON d.campaign_id=c.id
 GROUP BY c.id ORDER BY c.created_at DESC LIMIT 150");
 $rows=$q->fetchAll();
 $html='<section class="panel"><h2>Versandübersicht</h2><p>Freigegebene Infomails und Entwürfe mit nachvollziehbarem Status. Newsletter-Aussendungen haben weiterhin ihre eigene Versandübersicht.</p>';
 if(!$rows)$html.='<p class="muted">Noch keine Infomails vorhanden.</p>';
 else{
  $html.='<div class="tablewrap"><table><thead><tr><th>Turnier / Mail</th><th>Status</th><th>Empfänger</th><th>Angenommen</th><th>Fehler</th></tr></thead><tbody>';
  foreach($rows as $c){
   $html.='<tr><td>'.$e($c['tournament_name']).'<br><a href="'.boUrl('campaign',(int)$c['id']).'">'.$e($c['subject']).'</a></td><td>'.$e(boStatus($c['state'])).'</td><td>'.(int)$c['total'].'</td><td>'.(int)$c['accepted'].'</td><td>'.(int)$c['failed'].'</td></tr>';
  }
  $html.='</tbody></table></div>';
 }
 $html.='<p><a href="/newsletter-mail.php">Zum Newsletter-Versandprotokoll ↗</a></p></section>';
 boPage('Versandprotokoll',$html);
}
boGo();
