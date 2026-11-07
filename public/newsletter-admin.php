<?php
declare(strict_types=1);
header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');header('Cache-Control: no-store');header('X-Robots-Tag: noindex, nofollow');header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'; form-action \'self\'; frame-ancestors \'none\'');
$path=dirname(__DIR__).'/tennis-schiri-newsletter-config.php';
if(!is_file($path)){http_response_code(503);exit('Newsletter-Verwaltung noch nicht eingerichtet.');}
$cfg=require $path;
if(!is_array($cfg)||($cfg['enabled']??false)!==true||empty($cfg['admin_password_hash'])||empty($cfg['database'])){http_response_code(503);exit('Verwaltung nicht freigeschaltet.');}
session_name('ts_newsletter_admin');session_set_cookie_params(['httponly'=>true,'secure'=>true,'samesite'=>'Strict','path'=>'/']);session_start();
function render(string $body): never {
 header('Content-Type: text/html; charset=UTF-8');
 echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Tennis-Schiri Newsletter · Verwaltung</title><style>body{font:15px/1.6 system-ui;background:#f6f8f4;color:#092b36;margin:0}.wrap{max-width:1000px;margin:6vh auto;background:#fff;padding:32px;border-radius:16px}table{border-collapse:collapse;width:100%}td,th{border-bottom:1px solid #dce7e1;padding:10px;text-align:left}input,button{padding:10px;border-radius:6px}button{background:#092b36;color:white;border:0;cursor:pointer}a{color:#087a82}small{color:#567}</style><main class="wrap"><a href="https://tennis-schiri.de/" aria-label="Tennis-Schiri Startseite"><img src="/assets/branding/ts-logo-dunkel.png" alt="Tennis-Schiri" width="245" height="61" style="display:block;width:245px;max-width:100%;height:auto;border-radius:6px;margin-bottom:23px"></a><h1>Newsletter-Verwaltung</h1>'.$body.'</main></html>';exit;
}
if (!empty($_SESSION['authenticated']) && (int)($_SESSION['auth_at']??0)<time()-8*3600) {
 $_SESSION=[];session_regenerate_id(true);
}
if(($_POST['action']??'')==='login'){
 try {
  $rateDb=new PDO('sqlite:'.$cfg['database'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $rateDb->exec('PRAGMA busy_timeout=5000');
  $rateDb->exec('CREATE TABLE IF NOT EXISTS newsletter_admin_login_attempts (ip_hash TEXT NOT NULL, attempted_at INTEGER NOT NULL)');
  $rateDb->prepare('DELETE FROM newsletter_admin_login_attempts WHERE attempted_at<?')->execute([time()-3600]);
  $ipHash=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'),(string)($cfg['key']??''));
  $q=$rateDb->prepare('SELECT COUNT(*) FROM newsletter_admin_login_attempts WHERE ip_hash=? AND attempted_at>?');
  $q->execute([$ipHash,time()-900]);
  if((int)$q->fetchColumn()>=8){http_response_code(429);render('<p>Zu viele Anmeldeversuche. Bitte in 15 Minuten erneut versuchen.</p>');}
  if(password_verify((string)($_POST['password']??''),(string)$cfg['admin_password_hash'])){
   $rateDb->prepare('DELETE FROM newsletter_admin_login_attempts WHERE ip_hash=?')->execute([$ipHash]);
   session_regenerate_id(true);$_SESSION['authenticated']=true;$_SESSION['auth_at']=time();
   $_SESSION['csrf']=bin2hex(random_bytes(24));
   header('Location: /newsletter-admin.php',true,303);exit;
  }
  $rateDb->prepare('INSERT INTO newsletter_admin_login_attempts(ip_hash,attempted_at) VALUES(?,?)')->execute([$ipHash,time()]);
 }catch(Throwable $e){error_log('Newsletter admin login rate limit: '.$e->getMessage());http_response_code(503);render('<p>Anmeldung momentan nicht verfügbar.</p>');}
 usleep(400000);render('<p>Anmeldung fehlgeschlagen.</p><a href="/newsletter-admin.php">Zurück</a>');
}
if(empty($_SESSION['authenticated']))render('<form method="post"><input type="hidden" name="action" value="login"><label>Admin-Passwort <input type="password" name="password" required autocomplete="current-password"></label> <button>Einloggen</button></form>');
if(($_POST['action']??'')==='logout'){
 if(!hash_equals((string)($_SESSION['csrf']??''),(string)($_POST['csrf']??''))){http_response_code(403);exit;}
 $_SESSION=[];session_destroy();header('Location: /newsletter-admin.php',true,303);exit;
}
try{$db=new PDO('sqlite:'.$cfg['database'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}catch(Throwable $e){http_response_code(503);exit('Speicher nicht erreichbar.');}
$rows=$db->query('SELECT email,role,interests,created_at,confirmed_at,consent_version FROM subscribers ORDER BY created_at DESC')->fetchAll();
if(($_GET['export']??'')==='csv'){
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="tennis-schiri-newsletter-export.csv"');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['E-Mail','Zielgruppe','Interessen','Anmeldung','Bestätigt','Einwilligungsversion'],';');
 foreach($rows as $r){$cells=[$r['email'],$r['role'],$r['interests'],gmdate('c',(int)$r['created_at']),$r['confirmed_at']?gmdate('c',(int)$r['confirmed_at']):'', $r['consent_version']];fputcsv($out,array_map(static fn($v)=>preg_match('/^[=+@-]/',(string)$v)?"'".$v:$v,$cells),';');}
 exit;
}
$e=static fn($x)=>htmlspecialchars((string)$x,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$confirmed=count(array_filter($rows,static fn($r)=>$r['confirmed_at']!==null));$pending=count($rows)-$confirmed;
$html='<p><a href="/newsletter-mail.php">E-Mail-Verwaltung: Entwürfe und Rundmails</a></p><p><strong>'.count($rows).'</strong> Einträge · <strong>'.$confirmed.'</strong> bestätigt · <strong>'.$pending.'</strong> offen</p><p><a href="?export=csv">CSV exportieren (enthält personenbezogene Daten)</a></p><form method="post"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="'.$e($_SESSION['csrf']).'"><button>Abmelden</button></form><div style="overflow-x:auto"><table><thead><tr><th>E-Mail</th><th>Gruppe</th><th>Interessen</th><th>Eingetragen</th><th>Status</th></tr></thead><tbody>';
foreach($rows as $r)$html.='<tr><td>'.$e($r['email']).'</td><td>'.$e($r['role']).'</td><td>'.$e($r['interests']).'</td><td>'.date('d.m.Y H:i',(int)$r['created_at']).'</td><td>'.($r['confirmed_at']?'Bestätigt':'Offen').'</td></tr>';
$html.='</tbody></table></div><p><small>Nur bestätigte Adressen für Aussendungen verwenden. Exportdateien sicher aufbewahren und löschen, wenn sie nicht mehr gebraucht werden.</small></p>';
render($html);
