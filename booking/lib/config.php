<?php
/**
 * Загрузка конфигурации
 */

function loadConfig(): array
{
    $configPath = __DIR__ . '/../config.php';

    if (!file_exists($configPath)) {
        throw new RuntimeException('Файл config.php не найден. Скопируйте config.example.php в config.php и заполните значения.');
    }

    $config = require $configPath;

    if (!is_array($config)) {
        throw new RuntimeException('config.php должен возвращать массив.');
    }

    return $config;
}
