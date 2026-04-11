<?php
/**
 * Cron: очистка зависших pending-броней
 * Запуск: */5 * * * * /usr/bin/php /path/to/booking/cron/cleanup-pending.php
 */

// Только из CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Доступ запрещён');
}

require_once __DIR__ . '/../lib/bootstrap.php';

$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/cron.log';

$cronLog = function (string $message) use ($logFile): void {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
};

try {
    // Находим истёкшие pending-брони
    $stmt = $db->prepare(
        'SELECT id, event_id, guests FROM bookings WHERE status = "pending" AND expires_at < NOW()'
    );
    $stmt->execute();
    $expired = $stmt->fetchAll();

    if (empty($expired)) {
        $cronLog('Нет зависших броней');
        exit;
    }

    $count = 0;

    foreach ($expired as $booking) {
        $db->beginTransaction();

        try {
            // Обновляем статус брони
            $stmt = $db->prepare('UPDATE bookings SET status = "expired" WHERE id = ? AND status = "pending"');
            $stmt->execute([$booking['id']]);

            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                continue;
            }

            // Возвращаем места
            $stmt = $db->prepare(
                'UPDATE events SET booked = GREATEST(0, booked - ?),
                 status = IF(status = "sold_out", "active", status)
                 WHERE id = ?'
            );
            $stmt->execute([$booking['guests'], $booking['event_id']]);

            $db->commit();
            $count++;

            $cronLog("Бронь #{$booking['id']} истекла: возвращено {$booking['guests']} мест в событие #{$booking['event_id']}");
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $cronLog("Ошибка обработки брони #{$booking['id']}: " . $e->getMessage());
        }
    }

    $cronLog("Обработано $count зависших броней из " . count($expired));
} catch (Throwable $e) {
    $cronLog('Критическая ошибка: ' . $e->getMessage());
    exit(1);
}
