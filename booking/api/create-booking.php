<?php
/**
 * POST /booking/api/create-booking.php
 * Создание брони
 */

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/booking-service.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('METHOD_NOT_ALLOWED', 'Метод не поддерживается', [], 405);
}

// Rate limit
if (!checkRateLimit($db, 'create_booking', $config['booking']['rate_limit_per_minute'])) {
    jsonError('RATE_LIMIT', 'Слишком много попыток. Подождите минуту и попробуйте снова', [], 429);
}

$data = getJsonBody();

// Валидация
$errors = [];

if (empty($data['event_id']) || !is_numeric($data['event_id'])) {
    $errors['event_id'] = 'Не выбрано событие';
}

if (empty($data['guests']) || (int)$data['guests'] < 1 || (int)$data['guests'] > 20) {
    $errors['guests'] = 'Укажите количество гостей (от 1 до 20)';
}

if (empty($data['name']) || mb_strlen(trim($data['name'])) < 2) {
    $errors['name'] = 'Укажите ваше имя';
}

if (empty($data['phone']) || !validatePhone($data['phone'])) {
    $errors['phone'] = 'Некорректный номер телефона';
}

if (empty($data['email']) || !validateEmail($data['email'])) {
    $errors['email'] = 'Некорректный email';
}

if (empty($data['agree_terms'])) {
    $errors['agree_terms'] = 'Необходимо согласие с правилами';
}

if (empty($data['agree_privacy'])) {
    $errors['agree_privacy'] = 'Необходимо согласие на обработку данных';
}

if (!empty($errors)) {
    jsonError('VALIDATION_ERROR', 'Проверьте заполнение полей', $errors);
}

// Санитизация
$bookingData = [
    'event_id' => (int)$data['event_id'],
    'guests' => (int)$data['guests'],
    'name' => sanitizeString($data['name']),
    'phone' => sanitizeString($data['phone']),
    'email' => trim($data['email']),
    'comment' => !empty($data['comment']) ? sanitizeString($data['comment']) : null,
    'dietary' => $data['dietary'] ?? null,
];

try {
    $service = new BookingService($db, $config);
    $result = $service->createBooking($bookingData);
    jsonSuccess($result);
} catch (RuntimeException $e) {
    jsonError('BOOKING_ERROR', $e->getMessage());
} catch (Throwable $e) {
    logError('Ошибка создания брони: ' . $e->getMessage());
    jsonError('SERVER_ERROR', 'Внутренняя ошибка сервера. Попробуйте позже', [], 500);
}
