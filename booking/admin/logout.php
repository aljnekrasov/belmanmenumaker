<?php
/**
 * Админка — Выход
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

initAdminSession($config);
adminLogout();

header('Location: /booking/admin/index.php');
exit;
