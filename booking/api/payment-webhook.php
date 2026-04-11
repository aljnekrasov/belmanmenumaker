<?php
/**
 * POST /booking/api/payment-webhook.php
 * Приём webhook от платёжной системы (каркас для будущего подключения ЮKassa)
 */

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/booking-service.php';

// Автозагрузка PHPMailer
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    require_once __DIR__ . '/../lib/mailer.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$rawBody = file_get_contents('php://input');
$headers = getallheaders();

$service = new BookingService($db, $config);
$provider = $service->getPaymentProvider();

// TODO: При подключении ЮKassa:
// 1. Проверить подпись webhook через $provider->verifyWebhook($rawBody, $headers)
// 2. Распарсить данные через $provider->parseWebhook($rawBody)
// 3. Найти бронь по booking_token или payment_id
// 4. Обновить статус в зависимости от статуса платежа
// 5. Отправить письма клиенту и админу
// 6. Вернуть HTTP 200

if (!$provider->verifyWebhook($rawBody, $headers)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$paymentData = $provider->parseWebhook($rawBody);

// TODO: Реализовать обработку webhook при подключении реального провайдера
// $booking = найти бронь по $paymentData['booking_token'] или $paymentData['payment_id']
// Обновить статус брони
// Отправить письма

http_response_code(200);
echo json_encode(['ok' => true]);
