<?php
declare(strict_types=1);
require __DIR__ . '/security.php';
workshop_session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600, 'path' => $params['path'],
        'secure' => true, 'httponly' => true, 'samesite' => 'Strict',
    ]);
}
session_destroy();
header('Location: /', true, 303);
exit;
