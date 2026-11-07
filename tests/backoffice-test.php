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
 VALUES('turnier1','Bad Saarow Herbst','21.11.2026','Bad Saarow','https://tennis-schiri.de/turniere/#a','info','Hinweise: {{turnier}}','Hallo {{name}}!'.char(10).char(10).'{{zusatz}}','Treffpunkt um 09:30 Uhr',1,1)");
$row=$db->query('SELECT * FROM bo_campaigns LIMIT 1')->fetch();
boAssert(is_array($row),'Campaign not inserted');
boAssert($db->query('SELECT COUNT(*) FROM subscribers')->fetchColumn()==='1','Newsletter data changed');
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
boAssert($db->query("SELECT COUNT(*) FROM bo_deliveries WHERE state='pending'")->fetchColumn()==='1','Pending delivery missing');
fwrite(STDOUT,"Tennis-Schiri Backoffice: SQLite, Templates, escape, mail preview, tracking safety and separate contacts verified.\n");
