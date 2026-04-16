<?php
/**
 * Одноразовый скрипт установки: создаёт таблицы и админа.
 * УДАЛИТЬ СРАЗУ ПОСЛЕ ИСПОЛЬЗОВАНИЯ!
 *
 * Открыть в браузере: https://radius45.ru/booking/migrations/setup.php?key=radius2026setup
 */

$SECRET_KEY = 'radius2026setup';

if (!isset($_GET['key']) || $_GET['key'] !== $SECRET_KEY) {
    http_response_code(403);
    exit('Доступ запрещён');
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font-family:monospace; font-size:14px; padding:20px;">';
echo "=== Установка Радиус ===\n\n";

// Подключение к БД
require_once __DIR__ . '/../lib/bootstrap.php';

try {
    // 1. Создание таблиц
    echo "1. Создание таблиц...\n";
    $sql = file_get_contents(__DIR__ . '/001-init.sql');

    // Разбиваем по CREATE TABLE
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => strlen($s) > 10
    );

    foreach ($statements as $stmt) {
        $db->exec($stmt);
        // Извлекаем имя таблицы
        if (preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $stmt, $m)) {
            echo "   ✓ Таблица {$m[1]} создана\n";
        } else {
            echo "   ✓ Выполнено\n";
        }
    }

    // 2. Создание админа
    echo "\n2. Создание администратора...\n";
    $login = 'booking@radius45.ru';
    $password = 'N8x$T2wB7eR5';
    $name = 'Администратор';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Проверяем, существует ли уже
    $check = $db->prepare('SELECT id FROM admin_users WHERE login = ?');
    $check->execute([$login]);

    if ($check->fetch()) {
        echo "   — Админ '{$login}' уже существует, пропускаю\n";
    } else {
        $stmt = $db->prepare('INSERT INTO admin_users (login, password_hash, name) VALUES (?, ?, ?)');
        $stmt->execute([$login, $hash, $name]);
        echo "   ✓ Админ создан: {$login}\n";
    }

    echo "\n=== Готово! ===\n";
    echo "\nАдминка: https://radius45.ru/booking/admin/\n";
    echo "Логин: {$login}\n";
    echo "Пароль: {$password}\n";
    echo "\n⚠️  УДАЛИТЕ ЭТОТ ФАЙЛ ПРЯМО СЕЙЧАС!\n";
    echo "    Путь: booking/migrations/setup.php\n";

} catch (Exception $e) {
    echo "\n❌ ОШИБКА: " . $e->getMessage() . "\n";
}

echo '</pre>';
