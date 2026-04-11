<?php
/**
 * Авторизация для админки
 */

function initAdminSession(array $config): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => $config['security']['session_lifetime'],
        'path' => '/booking/admin/',
        'httponly' => true,
        'secure' => ($config['env'] === 'production'),
        'samesite' => 'Strict',
    ]);

    session_start();
}

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: /booking/admin/index.php');
        exit;
    }
}

function adminLogin(PDO $db, string $login, string $password): bool
{
    $stmt = $db->prepare('SELECT id, password_hash, name FROM admin_users WHERE login = ?');
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_name'] = $user['name'];
    session_regenerate_id(true);

    return true;
}

function adminLogout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}
