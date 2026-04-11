<?php
/**
 * Админка — Просмотр бронирования
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/booking-service.php';

initAdminSession($config);
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    header('Location: bookings.php');
    exit;
}

$service = new BookingService($db, $config);
$success = '';
$error = '';

// Отмена
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    try {
        $result = $service->cancelBooking($id);
        if ($result) {
            $success = 'Бронирование отменено, места возвращены';
        } else {
            $error = 'Невозможно отменить это бронирование (уже отменено или статус не позволяет)';
        }
    } catch (Throwable $e) {
        $error = 'Ошибка при отмене: ' . $e->getMessage();
    }
}

$booking = $service->getBooking($id);
if (!$booking) {
    header('Location: bookings.php');
    exit;
}

$typeLabels = ['dinner' => 'Ужин', 'tasting' => 'Дегустация'];
$statusLabels = [
    'pending'   => 'Ожидание оплаты',
    'paid'      => 'Оплачено',
    'cancelled' => 'Отменено',
    'refunded'  => 'Возврат',
    'expired'   => 'Истекло',
];

$canCancel = in_array($booking['status'], ['pending', 'paid'], true);

$pageTitle = 'Бронирование #' . $id;
require __DIR__ . '/_layout_start.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; max-width:800px;">
    <!-- Бронирование -->
    <div class="card">
        <h3 style="font-size:16px; color:var(--green); margin-bottom:16px;">Данные бронирования</h3>
        <table style="width:100%; font-size:14px;">
            <tr>
                <td style="padding:5px 0; color:#888; width:130px;">ID</td>
                <td style="padding:5px 0; font-family:'PT Mono',monospace;">#<?= (int)$booking['id'] ?></td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Статус</td>
                <td style="padding:5px 0;">
                    <span class="badge badge-<?= htmlspecialchars($booking['status']) ?>">
                        <?= $statusLabels[$booking['status']] ?? htmlspecialchars($booking['status']) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Гость</td>
                <td style="padding:5px 0; font-weight:700;"><?= htmlspecialchars($booking['name']) ?></td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Телефон</td>
                <td style="padding:5px 0;"><?= htmlspecialchars($booking['phone']) ?></td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Email</td>
                <td style="padding:5px 0;"><?= htmlspecialchars($booking['email']) ?></td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Кол-во гостей</td>
                <td style="padding:5px 0;"><?= (int)$booking['guests'] ?></td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Сумма</td>
                <td style="padding:5px 0; font-weight:700;"><?= number_format((int)$booking['total_amount'] / 100, 0, ',', "\u{00a0}") ?> &#8381;</td>
            </tr>
            <?php if (!empty($booking['comment'])): ?>
            <tr>
                <td style="padding:5px 0; color:#888;">Комментарий</td>
                <td style="padding:5px 0;"><?= htmlspecialchars($booking['comment']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($booking['dietary'])): ?>
            <tr>
                <td style="padding:5px 0; color:#888;">Диета</td>
                <td style="padding:5px 0;"><?= htmlspecialchars($booking['dietary']) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="padding:5px 0; color:#888;">Создано</td>
                <td style="padding:5px 0; font-size:13px;"><?= htmlspecialchars($booking['created_at']) ?></td>
            </tr>
            <?php if ($booking['paid_at']): ?>
            <tr>
                <td style="padding:5px 0; color:#888;">Оплачено</td>
                <td style="padding:5px 0; font-size:13px;"><?= htmlspecialchars($booking['paid_at']) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="padding:5px 0; color:#888;">Истекает</td>
                <td style="padding:5px 0; font-size:13px;"><?= htmlspecialchars($booking['expires_at']) ?></td>
            </tr>
            <?php if (!empty($booking['payment_id'])): ?>
            <tr>
                <td style="padding:5px 0; color:#888;">Payment ID</td>
                <td style="padding:5px 0; font-family:'PT Mono',monospace; font-size:12px;"><?= htmlspecialchars($booking['payment_id']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($booking['payment_provider'])): ?>
            <tr>
                <td style="padding:5px 0; color:#888;">Провайдер</td>
                <td style="padding:5px 0;"><?= htmlspecialchars($booking['payment_provider']) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Событие -->
    <div class="card">
        <h3 style="font-size:16px; color:var(--green); margin-bottom:16px;">Событие</h3>
        <table style="width:100%; font-size:14px;">
            <tr>
                <td style="padding:5px 0; color:#888; width:100px;">Название</td>
                <td style="padding:5px 0; font-weight:700;">
                    <a href="event-edit.php?id=<?= (int)$booking['event_id'] ?>" style="text-decoration:underline;">
                        <?= htmlspecialchars($booking['event_title']) ?>
                    </a>
                </td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Тип</td>
                <td style="padding:5px 0;"><?= $typeLabels[$booking['type']] ?? htmlspecialchars($booking['type']) ?></td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Дата</td>
                <td style="padding:5px 0;"><?= htmlspecialchars($booking['event_date']) ?></td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Время</td>
                <td style="padding:5px 0;"><?= htmlspecialchars(substr($booking['event_time'], 0, 5)) ?></td>
            </tr>
            <?php if ($booking['duration_minutes']): ?>
            <tr>
                <td style="padding:5px 0; color:#888;">Длительность</td>
                <td style="padding:5px 0;"><?= (int)$booking['duration_minutes'] ?> мин</td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="padding:5px 0; color:#888;">Цена за гостя</td>
                <td style="padding:5px 0;"><?= number_format((int)$booking['price_per_guest'] / 100, 0, ',', "\u{00a0}") ?> &#8381;</td>
            </tr>
            <?php if (!empty($booking['event_description'])): ?>
            <tr>
                <td style="padding:5px 0; color:#888;">Описание</td>
                <td style="padding:5px 0; font-size:13px;"><?= htmlspecialchars($booking['event_description']) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div style="margin-top:20px; display:flex; gap:12px;">
    <a href="bookings.php" class="btn btn-outline">&larr; К списку</a>
    <?php if ($canCancel): ?>
        <form method="post" onsubmit="return confirm('Вы уверены? Бронирование будет отменено, места возвращены.');" style="display:inline;">
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="btn btn-danger">Отменить бронирование</button>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
