<?php
/**
 * Админка — страница входа
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

initAdminSession($config);

// Если уже залогинен — на дашборд
if (isAdminLoggedIn()) {
    header('Location: /booking/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $error = 'Заполните все поля';
    } elseif (adminLogin($db, $login, $password)) {
        header('Location: /booking/admin/dashboard.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — Бельмас Админ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Sans:wght@400;700&family=PT+Mono&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green: #2F4A3F;
            --gold: #C5A55A;
            --cream: #FAF8F4;
            --red: #C0392B;
        }
        body {
            font-family: 'PT Sans', sans-serif;
            background: var(--green);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: var(--cream);
            border-radius: 12px;
            padding: 48px 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .login-card h1 {
            font-size: 24px;
            color: var(--green);
            margin-bottom: 8px;
            text-align: center;
        }
        .login-card .subtitle {
            text-align: center;
            color: var(--gold);
            font-family: 'PT Mono', monospace;
            font-size: 13px;
            margin-bottom: 32px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: var(--green);
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: 'PT Sans', sans-serif;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--gold);
        }
        .error {
            background: #fdecea;
            color: var(--red);
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: var(--green);
            color: var(--cream);
            border: none;
            border-radius: 8px;
            font-family: 'PT Sans', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover { background: #243b31; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Бельмас</h1>
        <div class="subtitle">Панель управления</div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="form-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" required
                       value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Войти</button>
        </form>
    </div>
</body>
</html>
