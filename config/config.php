<?php

define('ROOT_PATH', dirname(__DIR__) . '/');

require_once ROOT_PATH . 'app/Helpers/helpers.php';

loadEnv(ROOT_PATH . '.env');

date_default_timezone_set(env('APP_TIMEZONE', 'America/Lima'));

define('APP_NAME', env('APP_NAME', 'Sigametec'));
define('BASE_URL', env('BASE_URL', '/'));
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'sigametec'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

define('MAX_LOGIN_ATTEMPTS', (int) env('MAX_LOGIN_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_TIME', (int) env('LOGIN_LOCKOUT_TIME', 900));
define('MAX_PASSWORD_HISTORY', 5);
define('SESSION_TIMEOUT', (int) env('SESSION_TIMEOUT', 1800));

ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => SESSION_TIMEOUT,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

// Cierre automático de sesión por inactividad (30 minutos).
if (isset($_SESSION['user_id'])) {
    $lastActivity = $_SESSION['last_activity'] ?? time();
    if ((time() - $lastActivity) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        setFlash('error', 'Su sesión expiró por inactividad. Inicie sesión nuevamente.');
        redirect(BASE_URL . 'auth/iniciarSesion');
    }
    $_SESSION['last_activity'] = time();
    $_SESSION['session_expires_at'] = time() + SESSION_TIMEOUT;
}
