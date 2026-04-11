<?php
/**
 * Создание первого администратора
 * Использование: php migrations/create-admin.php login "Полное Имя" "пароль"
 * После использования — удалить или защитить.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Только из командной строки');
}

if ($argc < 4) {
    echo "Использование: php create-admin.php <login> <name> <password>\n";
    echo "Пример: php create-admin.php admin \"Иван Иванов\" \"mypassword\"\n";
    exit(1);
}

require_once __DIR__ . '/../lib/bootstrap.php';

$login = $argv[1];
$name = $argv[2];
$password = $argv[3];

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare(
        'INSERT INTO admin_users (login, password_hash, name) VALUES (?, ?, ?)'
    );
    $stmt->execute([$login, $hash, $name]);

    echo "Администратор '{$login}' успешно создан (ID: {$db->lastInsertId()})\n";
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        echo "Ошибка: пользователь с логином '{$login}' уже существует\n";
        exit(1);
    }
    echo "Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
