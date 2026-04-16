<?php
/**
 * Бизнес-логика бронирования
 */

require_once __DIR__ . '/payment/PaymentProvider.php';
require_once __DIR__ . '/payment/StubPaymentProvider.php';

class BookingService
{
    private PDO $db;
    private array $config;
    private PaymentProvider $payment;

    public function __construct(PDO $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
        $this->payment = $this->createPaymentProvider();
    }

    private function createPaymentProvider(): PaymentProvider
    {
        $provider = $this->config['payment_provider'] ?? 'stub';

        return match ($provider) {
            'stub' => new StubPaymentProvider($this->config['booking_base_url']),
            // 'yookassa' => new YooKassaPaymentProvider($this->config['yookassa']),
            default => throw new RuntimeException("Неизвестный платёжный провайдер: $provider"),
        };
    }

    /**
     * Получить список дат с событиями определённого типа
     */
    public function getAvailableDates(string $type): array
    {
        $stmt = $this->db->prepare(
            'SELECT event_date,
                    SUM(CASE WHEN status = "active" AND capacity > booked THEN 1 ELSE 0 END) as available_count
             FROM events
             WHERE type = ? AND event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
             AND status IN ("active", "sold_out")
             GROUP BY event_date
             ORDER BY event_date'
        );
        $stmt->execute([$type]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => [
            'date' => $row['event_date'],
            'has_availability' => (int)$row['available_count'] > 0,
        ], $rows);
    }

    /**
     * Получить слоты на конкретную дату
     */
    public function getSlots(string $type, string $date): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, event_time, title, description, duration_minutes, price_per_guest, capacity, booked, status
             FROM events
             WHERE type = ? AND event_date = ? AND status IN ("active", "sold_out")
             ORDER BY event_time'
        );
        $stmt->execute([$type, $date]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => [
            'event_id' => (int)$row['id'],
            'time' => substr($row['event_time'], 0, 5),
            'title' => $row['title'],
            'description' => $row['description'],
            'duration_minutes' => $row['duration_minutes'] ? (int)$row['duration_minutes'] : null,
            'price_rub' => (int)$row['price_per_guest'] / 100,
            'available_seats' => max(0, (int)$row['capacity'] - (int)$row['booked']),
            'capacity' => (int)$row['capacity'],
        ], $rows);
    }

    /**
     * Создать бронь (без оплаты — мгновенное подтверждение)
     */
    public function createBooking(array $data): array
    {
        $eventId = (int)$data['event_id'];
        $guests = (int)$data['guests'];

        $this->db->beginTransaction();

        try {
            // Блокировка события
            $stmt = $this->db->prepare(
                'SELECT id, type, event_date, event_time, title, capacity, booked, price_per_guest, status
                 FROM events WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();

            if (!$event) {
                $this->db->rollBack();
                throw new RuntimeException('Событие не найдено');
            }

            if ($event['status'] !== 'active') {
                $this->db->rollBack();
                throw new RuntimeException('Бронирование на это событие закрыто');
            }

            $available = (int)$event['capacity'] - (int)$event['booked'];
            if ($available < $guests) {
                $this->db->rollBack();
                throw new RuntimeException("Недостаточно мест. Доступно: $available");
            }

            // Генерация токена
            $bookingToken = bin2hex(random_bytes(32));
            $totalAmount = (int)$event['price_per_guest'] * $guests;

            // Создание брони — сразу confirmed
            $stmt = $this->db->prepare(
                'INSERT INTO bookings (event_id, guests, name, phone, email, comment, dietary, total_amount, booking_token, status, paid_at, expires_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "confirmed", NOW(), DATE_ADD(NOW(), INTERVAL 365 DAY))'
            );
            $stmt->execute([
                $eventId,
                $guests,
                $data['name'],
                $data['phone'],
                $data['email'],
                $data['comment'] ?? null,
                !empty($data['dietary']) ? json_encode($data['dietary'], JSON_UNESCAPED_UNICODE) : null,
                $totalAmount,
                $bookingToken,
            ]);

            $bookingId = (int)$this->db->lastInsertId();

            // Обновление счётчика мест
            $newBooked = (int)$event['booked'] + $guests;
            $newStatus = ($newBooked >= (int)$event['capacity']) ? 'sold_out' : 'active';

            $stmt = $this->db->prepare('UPDATE events SET booked = ?, status = ? WHERE id = ?');
            $stmt->execute([$newBooked, $newStatus, $eventId]);

            $this->db->commit();

            return [
                'booking_id' => $bookingId,
                'booking_token' => $bookingToken,
                'confirmed' => true,
                'event_title' => $event['title'],
                'event_date' => $event['event_date'],
                'event_time' => substr($event['event_time'], 0, 5),
                'guests' => $guests,
                'total_amount' => $totalAmount,
                'name' => $data['name'],
                'email' => $data['email'],
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Подтвердить оплату (заглушка)
     */
    public function confirmPayment(string $bookingToken): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.*, e.type, e.event_date, e.event_time, e.title as event_title, e.description as event_description, e.duration_minutes
             FROM bookings b
             JOIN events e ON b.event_id = e.id
             WHERE b.booking_token = ? AND b.status = "pending"'
        );
        $stmt->execute([$bookingToken]);
        $booking = $stmt->fetch();

        if (!$booking) {
            throw new RuntimeException('Бронирование не найдено или уже обработано');
        }

        // Проверка срока
        if (strtotime($booking['expires_at']) < time()) {
            throw new RuntimeException('Время на оплату истекло. Пожалуйста, создайте новое бронирование');
        }

        $stmt = $this->db->prepare(
            'UPDATE bookings SET status = "paid", paid_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$booking['id']]);

        return $booking;
    }

    /**
     * Отменить бронь (админ)
     */
    public function cancelBooking(int $bookingId): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'SELECT id, event_id, guests, status FROM bookings WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();

            if (!$booking || !in_array($booking['status'], ['pending', 'confirmed', 'paid'])) {
                $this->db->rollBack();
                return false;
            }

            // Отменяем бронь
            $stmt = $this->db->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ?');
            $stmt->execute([$bookingId]);

            // Возвращаем места
            $stmt = $this->db->prepare(
                'UPDATE events SET booked = GREATEST(0, booked - ?),
                 status = IF(status = "sold_out", "active", status)
                 WHERE id = ?'
            );
            $stmt->execute([$booking['guests'], $booking['event_id']]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Получить данные брони по ID
     */
    public function getBooking(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT b.*, e.type, e.event_date, e.event_time, e.title as event_title, e.description as event_description, e.duration_minutes, e.price_per_guest
             FROM bookings b
             JOIN events e ON b.event_id = e.id
             WHERE b.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Получить PaymentProvider
     */
    public function getPaymentProvider(): PaymentProvider
    {
        return $this->payment;
    }
}
