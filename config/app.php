<?php

define('APP_NAME', 'SIASISTE');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development');
define('APP_DEBUG', true);

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = rtrim(str_replace('/public/index.php', '', $scriptName), '/');
define('BASE_URL', $baseUrl);
define('CONFIG_PATH', __DIR__);
define('PUBLIC_PATH', __DIR__ . '/../public');
define('VIEWS_PATH', __DIR__ . '/../views');
define('STORAGE_PATH', __DIR__ . '/../storage');

date_default_timezone_set('America/Lima');
