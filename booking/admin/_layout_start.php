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

        @media (max-width: 768px) {
            .menu-toggle { display: flex; }
            .sidebar {
                width: min(280px, 80vw);
                transform: translateX(-100%);
                transition: transform .25s ease;
                box-shadow: 2px 0 16px rgba(0,0,0,.2);
            }
            .sidebar-nav a { padding: 14px 20px; font-size: 16px; }
            body.menu-open .sidebar { transform: translateX(0); }
            body.menu-open .sidebar-backdrop { display: block; }
            .main { margin-left: 0; padding: 70px 16px 24px; }
            .main h1 { font-size: 19px; margin-bottom: 18px; }

            /* Bigger touch targets for buttons */
            .btn { padding: 11px 20px; font-size: 15px; min-height: 44px; }
            .btn-sm { padding: 8px 14px; font-size: 13px; min-height: 36px; }

            /* Tables scroll horizontally + tighter cells */
            .data-table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                white-space: nowrap;
            }
            .data-table thead, .data-table tbody { display: table; width: 100%; }
            .data-table th, .data-table td { padding: 8px 10px; font-size: 13px; }

            /* Filter inputs stack */
            .filters { padding: 14px; gap: 10px; }
            .filters .form-group { width: 100%; }
            .filters input,
            .filters select,
            .filters textarea { min-width: 0 !important; width: 100% !important; }
            .filters .btn { width: 100%; }

            /* Static-column inline grids collapse to 1 column;
               auto-fit grids (dashboard cards) keep responsive behaviour */
            div[style*="grid-template-columns:1fr"] {
                grid-template-columns: 1fr !important;
            }

            /* Prevent iOS zoom on input focus (font-size must be ≥16px) */
            .form-group input,
            .form-group select,
            .form-group textarea,
            .filters input,
            .filters select { font-size: 16px !important; }

            /* Action button rows wrap */
            .actions, .form-actions { flex-wrap: wrap; }
            .card { padding: 18px 16px; }
        }

        /* Mobile hamburger toggle (hidden on desktop) */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 12px; left: 12px;
            z-index: 200;
            width: 44px; height: 44px;
            background: var(--green);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            padding: 0;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,.18);
        }
        .menu-toggle span {
            display: block;
            width: 20px; height: 2px;
            background: var(--cream);
            border-radius: 1px;
            transition: transform .2s, opacity .2s;
        }
        body.menu-open .menu-toggle span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        body.menu-open .menu-toggle span:nth-child(2) { opacity: 0; }
        body.menu-open .menu-toggle span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.4);
            z-index: 90;
        }
    </style>
</head>
<body>

<button type="button" class="menu-toggle" aria-label="Меню" id="menuToggle">
    <span></span><span></span><span></span>
</button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

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
