<?php
declare(strict_types=1);
define('TS_BACKOFFICE',true);
require dirname(__DIR__).'/public/admin/lib.php';

function boAssert(bool $condition,string $label): void {
 if(!$condition){fwrite(STDERR,"Backoffice test failed: ".$label."\n");exit(1);}
}
boAssert(boSafeEmail('test@example.com'),'Valid email rejected');
boAssert(!boSafeEmail("test@example.com\r\nBcc:other@example.com"),'CRLF email accepted');
boAssert(!boSafeEmail('not-an-address'),'Invalid email accepted');
boAssert(boEsc('<script>')==='&lt;script&gt;','HTML escaping broken');
boAssert(boStatus('draft')==='Entwurf'&&boStatus('sent')==='Vom Mailserver angenommen','Status mapping broken');
$templates=boTemplates();
boAssert(count($templates)>=3,'Default information mail templates missing');
boAssert(str_contains($templates['info']['body'],'{{zusatz}}'),'Additional notes placeholder missing');
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
boSchema($db);
boSchema($db); // Migration must be idempotent.
$schema=$db->query('PRAGMA table_info(bo_campaigns)')->fetchAll();
boAssert(in_array('notes',array_column($schema,'name'),true),'Additional notes column missing');
$db->exec("CREATE TABLE subscribers (id INTEGER PRIMARY KEY, email TEXT NOT NULL)");
$db->exec("INSERT INTO subscribers(email) VALUES('newsletter-example@example.com')");
$db->exec("INSERT INTO bo_contacts(tournament_id,name,email,created_at,updated_at) VALUES('turnier1','Max Beispiel','max@example.com',1,1)");
$db->exec("INSERT INTO bo_campaigns(tournament_id,tournament_name,tournament_date,tournament_location,tournament_url,template_type,subject,body,notes,created_at,updated_at)
 VALUES('turnier1','Bad Saarow Herbst','21.11.2026','Bad Saarow','https://tennis-schiri.de/turniere/#a','info','Hinweise: {{turnier}}','Hallo {{name}}!\n\n{{zusatz}}','Treffpunkt um 09:30 Uhr',1,1)");
$row=$db->query('SELECT * FROM bo_campaigns LIMIT 1')->fetch();
boAssert(is_array($row),'Campaign not inserted');
boAssert((int)$db->query('SELECT COUNT(*) FROM subscribers')->fetchColumn()===1,'Newsletter data changed');
$sub=boRenderTokens($row['body'],$row,['name'=>'Max Beispiel']);
boAssert(str_contains($sub,'Max Beispiel') && str_contains($sub,'Treffpunkt um 09:30 Uhr'),'Personalization broken');
[$subject,$plain,$html]=boMailContent($row,['name'=>'<Max & Co>'],true);
boAssert($subject==='Hinweise: Bad Saarow Herbst','Subject personalization broken');
boAssert(str_contains($plain,'Treffpunkt um 09:30 Uhr'),'Plaintext misses notes');
boAssert(str_contains($html,'&lt;Max &amp; Co&gt;'),'Unescaped HTML personalization');
boAssert(str_contains($html,'TEST / VORSCHAU'),'Test banner missing');
boAssert(str_contains($html,'turniere@tennis-schiri.de'),'Sender contact missing');
boAssert(str_contains($html,'https://tennis-schiri.de/turniere/#a'),'Event link missing');
boAssert(str_contains($html,'ts-logo-dunkel.png'),'Approved brand logo missing');
[,,$realHtml]=boMailContent($row,['name'=>'Max Beispiel'],false);
boAssert(!str_contains($realHtml,'TEST / VORSCHAU'),'Test banner in real email');
boAssert(!str_contains($realHtml,'Newsletter abbestellen'),'Organizational mails must not pretend to be opt-in newsletters');
$st=$db->prepare("INSERT INTO bo_deliveries(campaign_id,contact_id,email,name,state) VALUES(?,?,?,?, 'pending')");
$st->execute([$row['id'],1,'max@example.com','Max Beispiel']);
boAssert((int)$db->query("SELECT COUNT(*) FROM bo_deliveries WHERE state='pending'")->fetchColumn()===1,'Pending delivery missing');

$selectedInput=[
 'info_sections'=>['arrival','parking','fees','turnigo','myscore'],
 'info_arrival'=>'Bitte bis 09:30 Uhr anmelden.',
 'info_parking'=>'Parkplatz hinter der Tennishalle.',
 'info_fees'=>'25 Euro bar bezahlen.',
 'turnigo_url'=>'https://www.turnigo.de/turnier/123',
 'myscore_url'=>'https://myscore.live/turnier/123',
];
$info=boInfoFromPost($selectedInput);
boAssert(count(boInfoSelected($info))===5,'Checklist selection not preserved');
boAssert(boInfoValidHttps('https://www.turnigo.de/turnier/123','turnigo'),'Turnigo HTTPS URL rejected');
boAssert(boInfoValidHttps('https://myscore.live/turnier/123','myscore'),'MyScore HTTPS URL rejected');
boAssert(!boInfoValidHttps('https://turnigo.de.evil.example/id','turnigo'),'Spoofed Turnigo host accepted');
boAssert(!boInfoValidHttps('http://myscore.live/turnier/1','myscore'),'Insecure MyScore URL accepted');
boAssert(!boInfoValidHttps('https://user:pass@turnigo.de/abc','turnigo'),'URL with credentials accepted');
try{
 boInfoFromPost(['info_sections'=>['myscore'],'myscore_url'=>'https://not-myscore.live/abc']);
 boAssert(false,'Unexpected domain accepted');
}catch(InvalidArgumentException $expected){}
try{
 boInfoFromPost(['info_sections'=>['turnigo'],'turnigo_url'=>'']);
 boAssert(false,'Missing checked Turnigo URL accepted');
}catch(InvalidArgumentException $expected){}
$update=$db->prepare('UPDATE bo_campaigns SET info_sections=?,info_arrival=?,info_parking=?,info_fees=?,turnigo_url=?,myscore_url=? WHERE id=?');
$update->execute([$info['info_sections'],$info['info_arrival'],$info['info_parking'],$info['info_fees'],$info['turnigo_url'],$info['myscore_url'],$row['id']]);
$stored=$db->query('SELECT * FROM bo_campaigns WHERE id=1')->fetch();
boAssert(is_array($stored),'Updated campaign not found');
boAssert(count(boInfoSelected($stored))===5,'Persisted checklist missing');
boAssert(str_contains(boInfoEditor($stored),'checked'),'Saved checkbox not selected in editor');
[$blocks,$plainBlocks]=boInfoMailBlocks($stored);
boAssert(str_contains($blocks,'Turnigo')&&str_contains($blocks,'MyScore.live'),'Selected service links missing in HTML');
boAssert(str_contains($blocks,'https://www.turnigo.de/turnier/123'),'Turnigo destination missing');
boAssert(str_contains($plainBlocks,'https://myscore.live/turnier/123'),'MyScore URL missing in plain-text');
[, $mailPlain, $mailHtml]=boMailContent($stored,['name'=>'Max Beispiel'],false);
boAssert(str_contains($mailPlain,'Parkplatz hinter der Tennishalle'),'Plain-text parking info missing');
boAssert(str_contains($mailHtml,'25 Euro bar bezahlen.'),'HTML fees info missing');
boAssert(!str_contains($mailHtml,'TEST / VORSCHAU'),'Test banner must not appear in live mail');
$malicious=$stored;
$malicious['info_arrival']='<script>alert("test")</script>';
[$safeHtml,]=boInfoMailBlocks($malicious);
boAssert(!str_contains($safeHtml,'<script>')&&str_contains($safeHtml,'&lt;script&gt;'),'Checklist HTML not escaped');


/* Integration with Turnier-Guide 2.2 and venue snapshots. */
$profiles=boVenueList($db);
boAssert(count($profiles)>=5,'Bad Saarow, Neuenhagen Halle/Außen und allgemeine Standortvorlagen fehlen');
boAssert(isset($profiles['bad-saarow'])&&isset($profiles['neuenhagen'])&&isset($profiles['neuenhagen-halle'])&&isset($profiles['neuenhagen-aussen'])&&isset($profiles['general']),'Standortvorlagen-Schlüssel fehlen');
$profile=boVenueValidate([
 'venue_address'=>'Musterstraße 10, 12345 Beispielstadt',
 'venue_navigation'=>'https://maps.google.com/?q=example',
 'venue_surface'=>'Hartplatz (vor dem Turnier prüfen)',
 'venue_checkin'=>'Infotheke im Eingangsbereich',
 'venue_source'=>'Testquelle – aktuelle Bestätigung',
 'venue_verified'=>'yes'
]);
$profileJson=json_encode($profile,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
$q=$db->prepare('UPDATE bo_venues SET data=? WHERE id=?');
$q->execute([$profileJson,'bad-saarow']);
boAssert(boVenueData($profileJson)['address']==='Musterstraße 10, 12345 Beispielstadt','Stammdaten werden nicht gespeichert');
boAssert(!empty(boVenueData($profileJson)['_verified_at']),'Verifizierungsdatum fehlt');
$unverified=$profile;
$unverified['_verified_at']='';
$rawUnverified=json_encode($unverified,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
[$notSent,]=boVenueMailBlocks(['venue_data'=>$rawUnverified]);
boAssert($notSent==='','Unbestätigte historische Standortdaten wurden automatisch in E-Mail eingefügt');
[$confirmedHtml,]=boVenueMailBlocks(['venue_data'=>$profileJson]);
boAssert(str_contains($confirmedHtml,'Musterstraße'),'Bestätigte Standortdaten nicht nutzbar');
boAssert(!str_contains($confirmedHtml,'Testquelle – aktuelle Bestätigung'),'Interne Quelle wurde in E-Mail angezeigt');
try{boVenueValidate(['venue_navigation'=>'javascript:alert(1)']);boAssert(false,'Unsichere Navigation akzeptiert');}
catch(InvalidArgumentException $expected){}
$guideInput=[
 'guide_sections'=>['draw','warmup','payment','withdrawal'],
 'guide_draw'=>'https://www.tennis.de/turnier/123',
 'guide_warmup'=>'5 Minuten, nach aktueller Turnierausschreibung',
 'guide_payment'=>'Bar oder Karte nur nach Bestätigung',
 'guide_withdrawal'=>'Bitte aktuelle Ausschreibung beachten'
];
$guideJson=boGuideFromPost($guideInput);
boAssert(count(boGuideDecoded(['guide_data'=>$guideJson])['selected'])===4,'Guide-Checkliste hat Häkchen verloren');
boAssert(str_contains(boGuideEditor(['guide_data'=>$guideJson]),'guide_sections[]'),'Guide-Editor zeigt keine Kontrollkästchen');
try{boGuideFromPost(['guide_sections'=>['warmup'],'guide_warmup'=>'']);boAssert(false,'Unvollständiges Pflichtfeld akzeptiert');}
catch(InvalidArgumentException $expected){}
try{boGuideFromPost(['guide_sections'=>['draw'],'guide_draw'=>'http://example.com']);boAssert(false,'HTTP-Planlink akzeptiert');}
catch(InvalidArgumentException $expected){}
$db->prepare('UPDATE bo_campaigns SET venue_id=?,venue_data=?,guide_data=? WHERE id=?')
   ->execute(['bad-saarow',$profileJson,$guideJson,$row['id']]);
$new=$db->query('SELECT * FROM bo_campaigns WHERE id=1')->fetch();
boAssert($new['venue_id']==='bad-saarow','Standortwahl nicht gespeichert');
[,,$venueHtml]=boMailContent($new,['name'=>'Max Beispiel']);
boAssert(str_contains($venueHtml,'Musterstraße 10'),'Standort fehlt im Turnier-Guide');
boAssert(str_contains($venueHtml,'Bar oder Karte nur nach Bestätigung'),'Guide-Baustein fehlt im HTML');
boAssert(str_contains($venueHtml,'SPIELORT'),'Turnier-Hero enthält keine Ortskarte');
boAssert(str_contains($venueHtml,'https://www.tennis.de/turnier/123'),'Offizieller Auslosungslink fehlt');
$before=$new['venue_data'];
$db->prepare('UPDATE bo_venues SET data=? WHERE id=?')->execute(['{}','bad-saarow']);
$after=$db->query('SELECT venue_data FROM bo_campaigns WHERE id=1')->fetchColumn();
boAssert($before===$after,'Änderungen an Standorten verändern vorhandene Mail-Snapshots');


/* Pre-filled info mails: actual SQLite inserts, snapshots, preview and review gate. */
boAssert(boVenueBestGuess(['location'=>'Tennis Center Bad Saarow','title'=>'Winter-LK'])==='bad-saarow','Auto-recognition Bad Saarow broken');
boAssert(boVenueBestGuess(['location'=>'Neuenhagen bei Berlin','title'=>'Indoor-MIXED-Turnier'])==='neuenhagen-halle','Auto-recognition Neuenhagen Indoor broken');
boAssert(boVenueBestGuess(['location'=>'Neuenhagener Tennisclub 93','title'=>'LK-Turnier'])==='neuenhagen','Neuenhagen unknown indoor/outdoor should stay general');
boAssert(boVenueBestGuess(['location'=>'TC Rotation Friedrichsfelde','title'=>'ROHA Open'])==='general','Unknown location must use general template');
$seed=boKnownCampaignDefaults('bad-saarow','{}','info');
boAssert((int)$seed['defaults_review_required']===1 && $seed['defaults_reviewed_at']===null,'Prefilled draft must require review');
boAssert(str_contains($seed['info_arrival'],'30 Minuten'),'Known arrival guidance not prefilled');
boAssert(in_array('arrival',boInfoSelected($seed),true),'Arrival checkbox missing');
boAssert(in_array('contact',boInfoSelected($seed),true),'Contact checkbox missing');
$venueSeed=boVenueData($seed['venue_data']);
boAssert(str_contains($venueSeed['surface']??'','Laykold'),'Known Bad Saarow court surface proposal missing');
boAssert(empty($venueSeed['_verified_at']),'Historical location incorrectly marked verified');
$draftGuide=boGuideDecoded($seed);
boAssert(in_array('warmup',$draftGuide['selected'],true),'Known warmup not preselected');
boAssert(str_contains($draftGuide['values']['fees_detail'],'45 €'),'Historical price proposal missing');
boAssert(!in_array('fees_detail',$draftGuide['selected'],true),'Unverified prices must not be preselected');
$neuenhagen=boKnownCampaignDefaults('neuenhagen-halle','{}','info');
boAssert(str_contains($neuenhagen['venue_data'],'Advantage Red Court'),'Historic Neuenhagen indoor surface not offered for review');
boAssert(!in_array('scoring',boGuideDecoded($neuenhagen)['selected'],true),'Unverified match format selected without review');
$namedProfile=boKnownCampaignDefaults('bad-saarow',json_encode(['parking'=>'Eigene verifizierte Parkregel','_verified_at'=>'2026-11-07']),'info');
boAssert(boVenueData($namedProfile['venue_data'])['parking']==='Eigene verifizierte Parkregel','Verified user master was overwritten by history');

$testTournament=['id'=>'test-prefilled-2027','title'=>'Bad Saarow Turniertest','date'=>'24.01.2027','location'=>'Tennis Center Bad Saarow','url'=>'https://tennis-schiri.de/turniere/#turnier-bad-saarow-2027'];
$demoId=boCreateKnownCampaign($db,$testTournament,boTemplates()['info'],'info','bad-saarow','{}',time());
$draft=boCampaign($db,$demoId);
boAssert((int)$draft['defaults_review_required']===1 && empty($draft['defaults_reviewed_at']),'Created campaign skipped review flag');
boAssert(str_contains($draft['info_arrival'],'30 Minuten'),'Created SQLite campaign missing proposed arrival');
boAssert(str_contains($draft['venue_data'],'Laykold'),'Created SQLite campaign missing proposed venue');
boAssert(str_contains(boInfoEditor($draft),'checked'),'Checkboxes are not preset in admin');
boAssert(str_contains(boCampaignVenueEditor($draft),'Laykold'),'Editable venue proposal missing');
[,,$notReviewedHtml]=boMailContent($draft,['name'=>'Max Muster'],false);
boAssert(!str_contains($notReviewedHtml,'Laykold'),'Unconfirmed venue details leaked into actual mail');
[,,$previewWithHistory]=boMailContent($draft,['name'=>'Max Muster'],true);
boAssert(str_contains($previewWithHistory,'Laykold') && str_contains($previewWithHistory,'NOCH NICHT GEPRÜFTE'),'Preview must explicitly show unverified proposals');
$updateInput=['campaign_venue_surface'=>'Hartplatz/Laykold','campaign_venue_parking'=>'Parkplätze geprüft','campaign_venue_checkin'=>'Counter','campaign_venue_catering'=>'Gastronomie'];
$approvedVenue=boCampaignVenueFromPost($updateInput,$draft,true);
boAssert(!empty(boVenueData($approvedVenue)['_verified_at']),'Explicit review did not mark this campaign venue as checked');
$before=boVenueData($approvedVenue)['_source']??'';
boAssert(str_contains($before,'Bad-Saarow'),'Historical source was lost during editing');
$demoUpdate=$db->prepare('UPDATE bo_campaigns SET venue_data=?,defaults_reviewed_at=? WHERE id=?');
$demoUpdate->execute([$approvedVenue,time(),$demoId]);
$reviewed=boCampaign($db,$demoId);
[,,$reviewedHtml]=boMailContent($reviewed,['name'=>'Max Muster'],false);
boAssert(str_contains($reviewedHtml,'Parkplätze geprüft'),'Verified venue snapshot not included in real HTML');
$legacyDraft=['venue_id'=>'bad-saarow','venue_data'=>'{}','template_type'=>'info','info_arrival'=>'Meine schon gespeicherte Meldezeit','info_sections'=>'[]','guide_data'=>'{}'];
$merged=boMergeKnownCampaign($legacyDraft,'bad-saarow');
boAssert($merged['info_arrival']==='Meine schon gespeicherte Meldezeit','Existing draft custom data overwritten');
boAssert(str_contains($merged['venue_data'],'Laykold'),'Existing draft not enriched with known suggestions');
boAssert(empty($merged['defaults_reviewed_at']),'Imported suggestions must reset review');

fwrite(STDOUT,"Tennis-Schiri Backoffice: SQLite, Templates, escape, mail preview, tracking safety and separate contacts verified.\n");
