<?php
$rootAutoload = __DIR__ . '/../vendor/autoload.php';

if (file_exists($rootAutoload)) {
    require_once $rootAutoload;
}

$extensionAutoload = __DIR__ . '/../extension/lookersolution/vendor/autoload.php';

if (file_exists($extensionAutoload)) {
    require_once $extensionAutoload;
}

if (!defined('DIR_EXTENSION')) {
    define('DIR_EXTENSION', __DIR__ . '/../extension/');
}

if (!defined('DB_PREFIX')) {
    define('DB_PREFIX', 'oc_');
}

if (!defined('DIR_LOGS')) {
    define('DIR_LOGS', sys_get_temp_dir() . '/');
}
