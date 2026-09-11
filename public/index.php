<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../config/config.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Response;

header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self' https://www.google.com https://cdn.jsdelivr.net; form-action 'self'");

require_once __DIR__ . '/../config/routes.php';

try {
    Router::dispatch();
} catch (\Exception $e) {
    if (APP_DEBUG) {
        Response::json(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 500);
    } else {
        Response::json(['error' => 'Error interno del servidor'], 500);
    }
}
