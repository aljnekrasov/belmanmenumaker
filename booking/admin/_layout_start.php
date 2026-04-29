<?php
/**
 * Общий layout — начало страницы (sidebar + header)
 * Ожидается переменная $pageTitle
 */
$_currentPage = basename($_SERVER['SCRIPT_NAME']);
$_adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Админ');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Бельмас Админ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Sans:wght@400;700&family=PT+Mono&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green: #2F4A3F;
            --green-light: #3a5e4f;
            --gold: #C5A55A;
            --cream: #FAF8F4;
            --red: #C0392B;
            --sidebar-w: 220px;
        }
        body {
            font-family: 'PT Sans', sans-serif;
            background: var(--cream);
            color: #333;
            min-height: 100vh;
        }
        a { color: var(--green); }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--green);
            color: var(--cream);
            display: flex;
            flex-direction: column;
            z-index: 100;
        }
        .sidebar-brand {
            padding: 24px 20px 16px;
            font-size: 20px;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-brand small {
            display: block;
            font-family: 'PT Mono', monospace;
            font-size: 11px;
            color: var(--gold);
            font-weight: 400;
            margin-top: 2px;
        }
        .sidebar-nav { flex: 1; padding: 16px 0; }
        .sidebar-nav a {
            display: block;
            padding: 10px 20px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 15px;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .sidebar-nav a.active {
            border-left: 3px solid var(--gold);
            padding-left: 17px;
        }
        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 13px;
        }
        .sidebar-footer .admin-name { color: var(--gold); font-weight: 700; }
        .sidebar-footer a { color: rgba(255,255,255,0.6); text-decoration: none; font-size: 13px; }
        .sidebar-footer a:hover { color: #fff; }

        /* Main area */
        .main {
            margin-left: var(--sidebar-w);
            padding: 32px 36px;
            min-height: 100vh;
        }
        .main h1 {
            font-size: 22px;
            color: var(--green);
            margin-bottom: 24px;
        }

        /* Common table styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .data-table thead tr { background: var(--green); color: var(--cream); }
        .data-table th { padding: 10px 14px; text-align: left; font-size: 13px; font-weight: 700; }
        .data-table td { padding: 10px 14px; font-size: 14px; border-bottom: 1px solid #eee; }
        .data-table tbody tr:nth-child(even) { background: #faf9f6; }
        .data-table tbody tr:hover { background: #f0efe8; }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 8px 18px;
            border: none;
            border-radius: 8px;
            font-family: 'PT Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-primary { background: var(--green); color: var(--cream); }
        .btn-primary:hover { background: #243b31; }
        .btn-gold { background: var(--gold); color: #fff; }
        .btn-gold:hover { background: #b3943f; }
        .btn-danger { background: var(--red); color: #fff; }
        .btn-danger:hover { background: #a93226; }
        .btn-outline {
            background: transparent;
            border: 2px solid var(--green);
            color: var(--green);
        }
        .btn-outline:hover { background: var(--green); color: var(--cream); }
        .btn-sm { padding: 5px 12px; font-size: 13px; }

        /* Forms */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: var(--green);
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 9px 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: 'PT Sans', sans-serif;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--gold);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }

        /* Alerts */
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; }
        .alert-success { background: #d5f5e3; color: #1e7e34; }
        .alert-error { background: #fdecea; color: var(--red); }

        /* Filters bar */
        .filters {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
            margin-bottom: 20px;
            background: #fff;
            padding: 16px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .filters .form-group { margin-bottom: 0; }
        .filters .form-group label { font-size: 12px; margin-bottom: 3px; }
        .filters .form-group input,
        .filters .form-group select { font-size: 14px; padding: 7px 10px; }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            font-family: 'PT Mono', monospace;
        }
        .badge-active { background: #d5f5e3; color: #1e7e34; }
        .badge-closed { background: #eee; color: #666; }
        .badge-sold_out { background: #fdecea; color: var(--red); }
        .badge-pending { background: #fef9e7; color: #b7950b; }
        .badge-confirmed { background: #d5f5e3; color: #1e7e34; }
        .badge-paid { background: #d5f5e3; color: #1e7e34; }
        .badge-cancelled { background: #eee; color: #666; }
        .badge-refunded { background: #d6eaf8; color: #2471a3; }
        .badge-expired { background: #fdecea; color: var(--red); }

        .card {
            background: #fff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }

        /* Mobile top nav */
        .topbar { display: none; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 16px 14px; }

            .topbar {
                display: block;
                position: sticky;
                top: 0;
                z-index: 50;
                background: var(--green);
                color: var(--cream);
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }
            .topbar-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 16px;
                border-bottom: 1px solid rgba(255,255,255,0.08);
            }
            .topbar-brand {
                font-size: 16px;
                font-weight: 700;
            }
            .topbar-brand small {
                color: var(--gold);
                font-family: 'PT Mono', monospace;
                font-size: 10px;
                margin-left: 6px;
                font-weight: 400;
            }
            .topbar-user {
                font-size: 12px;
                color: rgba(255,255,255,0.85);
            }
            .topbar-user a {
                color: var(--gold);
                text-decoration: none;
                margin-left: 10px;
            }
            .topbar-nav {
                display: flex;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }
            .topbar-nav::-webkit-scrollbar { display: none; }
            .topbar-nav a {
                flex-shrink: 0;
                padding: 11px 16px;
                color: rgba(255,255,255,0.7);
                text-decoration: none;
                font-size: 14px;
                font-weight: 700;
                white-space: nowrap;
                border-bottom: 2px solid transparent;
                transition: color 0.15s, border-color 0.15s;
            }
            .topbar-nav a.active {
                color: #fff;
                border-bottom-color: var(--gold);
            }

            /* Глобально убираем горизонтальную прокрутку страницы на мобилке */
            html, body { overflow-x: hidden; max-width: 100vw; }
            .main { max-width: 100vw; overflow-x: hidden; }

            .main h1 { font-size: 19px; margin-bottom: 16px; }

            /* Скроллим только саму таблицу, если не влезает */
            .table-wrap {
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 10px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                background: #fff;
                margin-bottom: 12px;
            }
            .table-wrap .data-table {
                box-shadow: none;
                border-radius: 0;
                margin: 0;
                min-width: max-content;
            }

            .data-table { font-size: 13px; }
            .data-table th, .data-table td { padding: 8px 10px; white-space: nowrap; }
            .card { padding: 16px; max-width: 100%; }
            .filters { max-width: 100%; }

            /* Статусы в таблицах — только цветной кружок без текста */
            .data-table td .badge {
                font-size: 0;
                padding: 0;
                width: 12px;
                height: 12px;
                border-radius: 50%;
                vertical-align: middle;
                line-height: 0;
            }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-head">
        <div class="topbar-brand">Бельмас<small>Админ</small></div>
        <div class="topbar-user"><?= $_adminName ?> · <a href="logout.php">выйти</a></div>
    </div>
    <nav class="topbar-nav">
        <a href="dashboard.php" class="<?= $_currentPage === 'dashboard.php' ? 'active' : '' ?>">Дашборд</a>
        <a href="events.php" class="<?= in_array($_currentPage, ['events.php','event-edit.php','event-delete.php'], true) ? 'active' : '' ?>">События</a>
        <a href="event-edit.php" class="<?= $_currentPage === 'event-edit.php' && empty($_GET['id']) ? 'active' : '' ?>">+ Новое</a>
        <a href="bookings.php" class="<?= in_array($_currentPage, ['bookings.php','booking-view.php'], true) ? 'active' : '' ?>">Брони</a>
    </nav>
</header>

<aside class="sidebar">
    <div class="sidebar-brand">
        Бельмас
        <small>Админ-панель</small>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="<?= $_currentPage === 'dashboard.php' ? 'active' : '' ?>">Дашборд</a>
        <a href="events.php" class="<?= $_currentPage === 'events.php' ? 'active' : '' ?>">События</a>
        <a href="event-edit.php" class="<?= $_currentPage === 'event-edit.php' ? 'active' : '' ?>">Новое событие</a>
        <a href="bookings.php" class="<?= $_currentPage === 'bookings.php' ? 'active' : '' ?>">Бронирования</a>
    </nav>
    <div class="sidebar-footer">
        <span class="admin-name"><?= $_adminName ?></span><br>
        <a href="logout.php">Выйти</a>
    </div>
</aside>

<main class="main">
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
