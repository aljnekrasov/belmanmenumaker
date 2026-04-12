<?php
/**
 * GET/POST /booking/api/confirm-payment.php
 * Заглушка подтверждения оплаты — в будущем заменяется на ЮKassa webhook
 */

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/booking-service.php';

// Автозагрузка PHPMailer (если vendor есть)
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    require_once __DIR__ . '/../lib/mailer.php';
}

header('Content-Type: text/html; charset=utf-8');
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo renderThankYouPage('Ошибка', 'Неверная ссылка подтверждения.');
    exit;
}

try {
    $service = new BookingService($db, $config);
    $booking = $service->confirmPayment($token);

    // Отправка писем
    if (class_exists('Mailer')) {
        $mailer = new Mailer($config);
        $mailer->sendBookingConfirmation($booking, $booking);
        $mailer->sendAdminNotification($booking, $booking);
    }

    echo renderThankYouPage('Спасибо за бронирование!', '', $booking);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo renderThankYouPage('Ошибка', $e->getMessage());
} catch (Throwable $e) {
    logError('Ошибка подтверждения оплаты: ' . $e->getMessage());
    http_response_code(500);
    echo renderThankYouPage('Ошибка', 'Внутренняя ошибка сервера. Свяжитесь с нами: hello@belmaswinery.ru');
}

function renderThankYouPage(string $title, string $message, ?array $booking = null): string
{
    $html = '<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . htmlspecialchars($title) . ' — Радиус</title>
<link href="https://fonts.googleapis.com/css2?family=PT+Sans:wght@400;700&family=PT+Mono&display=swap" rel="stylesheet">
<style>
:root { --green: #2F4A3F; --gold: #C5A55A; --cream: #FAF8F4; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "PT Sans", sans-serif; background: var(--cream); color: #333; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
.card { background: #fff; max-width: 560px; width: 100%; padding: 60px 48px; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
h1 { color: var(--green); font-size: 28px; margin-bottom: 16px; }
.subtitle { color: #777; font-size: 16px; margin-bottom: 32px; }
.details { text-align: left; margin: 24px 0; }
.detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
.detail-label { font-family: "PT Mono", monospace; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #999; }
.detail-value { font-size: 15px; font-weight: 700; }
.btn { display: inline-block; padding: 14px 36px; background: var(--gold); color: var(--green); text-decoration: none; font-family: "PT Mono", monospace; font-size: 13px; letter-spacing: .06em; text-transform: uppercase; margin-top: 24px; transition: background .3s; }
.btn:hover { background: #d4b96e; }
.error-msg { color: #c0392b; font-size: 16px; line-height: 1.6; }
</style>
</head>
<body>
<div class="card">';

    $html .= '<h1>' . htmlspecialchars($title) . '</h1>';

    if ($booking !== null) {
        $html .= '<p class="subtitle">Ваше бронирование подтверждено</p>';
        $html .= '<div class="details">';
        $html .= '<div class="detail-row"><span class="detail-label">Событие</span><span class="detail-value">' . htmlspecialchars($booking['event_title'] ?? '') . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Дата</span><span class="detail-value">' . date('d.m.Y', strtotime($booking['event_date'])) . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Время</span><span class="detail-value">' . substr($booking['event_time'], 0, 5) . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Гостей</span><span class="detail-value">' . (int)$booking['guests'] . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Сумма</span><span class="detail-value">' . number_format((int)$booking['total_amount'] / 100, 0, ',', ' ') . ' ₽</span></div>';
        $html .= '</div>';
        $html .= '<p style="color:#555;font-size:14px;margin-top:16px;">Подтверждение отправлено на <strong>' . htmlspecialchars($booking['email']) . '</strong></p>';
    } elseif ($message) {
        $html .= '<p class="error-msg">' . htmlspecialchars($message) . '</p>';
    }

    $html .= '<a href="/" class="btn">Вернуться на сайт</a>';
    $html .= '</div></body></html>';

    return $html;
}
