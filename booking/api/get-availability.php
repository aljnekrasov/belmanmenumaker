<?php
/**
 * GET /booking/api/get-availability.php
 * Параметры: type (обязательный), date (опциональный)
 */

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/booking-service.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('METHOD_NOT_ALLOWED', 'Метод не поддерживается', [], 405);
}

$type = $_GET['type'] ?? '';
if (!in_array($type, ['dinner', 'tasting'])) {
    jsonError('VALIDATION_ERROR', 'Параметр type обязателен: dinner или tasting', ['type' => 'Допустимые значения: dinner, tasting']);
}

$service = new BookingService($db, $config);

$date = $_GET['date'] ?? '';

if ($date === '') {
    // Список дат
    $dates = $service->getAvailableDates($type);
    jsonSuccess([
        'type' => $type,
        'dates' => $dates,
    ]);
} else {
    if (!validateDate($date)) {
        jsonError('VALIDATION_ERROR', 'Некорректный формат даты', ['date' => 'Формат: YYYY-MM-DD']);
    }

    $slots = $service->getSlots($type, $date);
    jsonSuccess([
        'type' => $type,
        'date' => $date,
        'slots' => $slots,
    ]);
}
