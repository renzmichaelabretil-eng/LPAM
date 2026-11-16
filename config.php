<?php

$localConfig = __DIR__ . '/config.local.php';
$hostingConfig = dirname(__DIR__) . '/lpam-config.php';

if (is_file($localConfig)) {
    require $localConfig;
} elseif (is_file($hostingConfig)) {
    require $hostingConfig;
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', (string) getenv('DB_NAME'));
    define('DB_USER', (string) getenv('DB_USER'));
    define('DB_PASS', (string) getenv('DB_PASS'));
}

date_default_timezone_set('Asia/Manila');
