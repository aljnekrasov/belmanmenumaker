<?php
/**
 * Bootstrap — подключается в начале каждого эндпоинта
 */

declare(strict_types=1);

// Настройка ошибок
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$config = loadConfig();

// В production не показываем ошибки в браузер
if ($config['env'] === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    ini_set('display_errors', '1');
}

// Подключение к БД
$db = getDb($config);

// Часовой пояс
date_default_timezone_set('Europe/Moscow');
