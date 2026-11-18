<?php
declare(strict_types=1);
require __DIR__ . '/security.php';
workshop_require_auth(false);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function room_path(string $room): string {
    return __DIR__ . '/_private/live-state/' . $room . '.json';
}
function valid_room(mixed $room): bool {
    return is_string($room) && (bool)preg_match('/^[A-HJ-NP-Z2-9]{6}$/D', $room);
}
function load_room(string $code): ?array {
    $file=room_path($code);
    if (!is_file($file)) return null;
    $data=json_decode((string)@file_get_contents($file),true);
    if (!is_array($data)||($data['room']??'')!==$code) return null;
    if (($data['updated_at']??0) < time()-8*3600) return null;
    return $data;
}
function response_view(array $data): array {
    $slide=(int)($data['slide']??1);
    $votes=$data['votes'][(string)$slide]??['A'=>0,'B'=>0,'C'=>0];
    return ['ok'=>true,'room'=>$data['room'],'active'=>(bool)($data['active']??false),
        'slide'=>$slide,'updated_at'=>(int)($data['updated_at']??0),
        'votes'=>$votes,'total'=>array_sum($votes)];
}
function room_update(string $code, callable $fn): ?array {
    $file=room_path($code);
    if (!is_file($file)) return null;
    $handle=@fopen($file,'c+');
    if (!$handle) return null;
    try {
        if (!flock($handle,LOCK_EX)) return null;
        rewind($handle);
        $raw=stream_get_contents($handle);
        $state=is_string($raw)&&$raw!==''?json_decode($raw,true):null;
        if (!is_array($state)||($state['room']??'')!==$code) return null;
        $state=$fn($state);
        if ($state===null) return null;
        rewind($handle);ftruncate($handle,0);
        fwrite($handle,json_encode($state,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        fflush($handle);
        return $state;
    } finally {
        flock($handle,LOCK_UN);
        fclose($handle);
    }
}
$method=$_SERVER['REQUEST_METHOD']??'GET';
$action=$_GET['op']??'status';
if ($method==='GET') {
    if ($action==='mine') {
        $code=$_SESSION['workshop_host_room']??null;
        session_write_close();
        if (!valid_room($code)) respond(200,['ok'=>true,'room'=>null]);
        $state=load_room($code);
        respond(200,$state?response_view($state):['ok'=>true,'room'=>null]);
    }
    if ($action!=='status') respond(400,['error'=>'Ungültige Aktion']);
    $code=$_GET['room']??'';
    if (!valid_room($code)) respond(400,['error'=>'Ungültiger Raumcode']);
    session_write_close();
    $state=load_room($code);
    respond($state?200:404,$state?response_view($state):['error'=>'Raum nicht gefunden oder abgelaufen']);
}
if ($method!=='POST') respond(405,['error'=>'Nicht erlaubt']);
$data=json_decode((string)file_get_contents('php://input'),true);
if (!is_array($data)) respond(400,['error'=>'Ungültige Eingabe']);

if ($action==='create') {
    $dir=__DIR__.'/_private/live-state';
    if (!is_dir($dir)&&!@mkdir($dir,0750,true)&&!is_dir($dir)) respond(503,['error'=>'Live-Raum konnte nicht erstellt werden']);
    $alphabet='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $room='';
    for($tries=0;$tries<8;$tries++) {
        $candidate='';
        for($i=0;$i<6;$i++) $candidate.=$alphabet[random_int(0,strlen($alphabet)-1)];
        if (!file_exists(room_path($candidate))) {$room=$candidate;break;}
    }
    if ($room==='') respond(503,['error'=>'Raumcode nicht verfügbar']);
    $state=['room'=>$room,'active'=>true,'slide'=>max(1,min(136,(int)($data['slide']??1))),
        'updated_at'=>time(),'votes'=>[]];
    $created=@file_put_contents(room_path($room),json_encode($state),LOCK_EX);
    if ($created===false) respond(503,['error'=>'Live-Raum kann nicht gespeichert werden']);
    $_SESSION['workshop_host_room']=$room;
    $_SESSION['workshop_host_token']=bin2hex(random_bytes(24));
    respond(200,response_view($state)+['host_token'=>$_SESSION['workshop_host_token']]);
}
$code=$data['room']??'';
if (!valid_room($code)) respond(400,['error'=>'Ungültiger Raumcode']);
if ($action==='vote') {
    $slide=(int)($data['slide']??0);
    $answer=$data['answer']??'';
    if ($slide<1||$slide>136||!in_array($answer,['A','B','C'],true)) respond(400,['error'=>'Ungültige Antwort']);
    if (!isset($_SESSION['workshop_votes'])) $_SESSION['workshop_votes']=[];
    $key=$code.':'.$slide;
    if (isset($_SESSION['workshop_votes'][$key])) respond(409,['error'=>'Bereits abgestimmt']);
    $state=room_update($code,static function(array $state) use($slide,$answer): ?array {
        if (empty($state['active'])||(int)$state['slide']!==$slide) return null;
        if (!isset($state['votes'][(string)$slide])) $state['votes'][(string)$slide]=['A'=>0,'B'=>0,'C'=>0];
        ++$state['votes'][(string)$slide][$answer];
        return $state;
    });
    if (!$state) respond(409,['error'=>'Die Frage ist nicht mehr aktiv']);
    $_SESSION['workshop_votes'][$key]=$answer;
    respond(200,response_view($state)+['your_vote'=>$answer]);
}
$host=$_SESSION['workshop_host_room']??null;
$token=$_SESSION['workshop_host_token']??null;
$sent=$data['host_token']??null;
if ($code!==$host||!is_string($token)||!is_string($sent)||!hash_equals($token,$sent)) {
    respond(403,['error'=>'Nur der Referent kann diesen Raum steuern']);
}
if ($action==='slide') {
    $slide=max(1,min(136,(int)($data['slide']??1)));
    $state=room_update($code,static function(array $state) use($slide): array {
        $state['slide']=$slide;$state['updated_at']=time();$state['active']=true;return $state;
    });
    respond($state?200:404,$state?response_view($state):['error'=>'Raum nicht gefunden']);
}
if ($action==='end') {
    $state=room_update($code,static function(array $state): array {
        $state['active']=false;$state['updated_at']=time();return $state;
    });
    unset($_SESSION['workshop_host_room'],$_SESSION['workshop_host_token']);
    respond($state?200:404,$state?response_view($state):['error'=>'Raum nicht gefunden']);
}
respond(400,['error'=>'Ungültige Aktion']);
