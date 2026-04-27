<?php
/**
 * Админка — Удаление события с каскадным удалением броней
 *
 * GET  ?id=N            — страница подтверждения со списком связанных броней
 * POST ?id=N confirm=1  — фактическое удаление (сначала брони, затем событие)
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

initAdminSession($config);
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: events.php');
    exit;
}

// Загружаем событие
$stmt = $db->prepare('SELECT * FROM events WHERE id = ?');
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: events.php');
    exit;
}

// Связанные брони
$stmt = $db->prepare(
    'SELECT id, name, phone, email, guests, total_amount, status, created_at
       FROM bookings
      WHERE event_id = ?
   ORDER BY created_at DESC'
);
$stmt->execute([$id]);
$bookings = $stmt->fetchAll();

$activeStatuses = ['pending', 'confirmed', 'paid'];
$activeBookings = array_filter($bookings, fn($b) => in_array($b['status'], $activeStatuses, true));
$activeCount = count($activeBookings);
$activeGuests = array_sum(array_map(fn($b) => (int)$b['guests'], $activeBookings));

$errors = [];

// POST — удаляем
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === '1') {
    try {
        $db->beginTransaction();

        // Сначала удаляем брони (FK стоит ON DELETE RESTRICT)
        $delB = $db->prepare('DELETE FROM bookings WHERE event_id = ?');
        $delB->execute([$id]);
        $deletedBookings = $delB->rowCount();

        // Затем событие
        $delE = $db->prepare('DELETE FROM events WHERE id = ?');
        $delE->execute([$id]);

        $db->commit();

        header('Location: events.php?deleted=' . $id . '&bookings=' . $deletedBookings);
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $errors[] = 'Ошибка при удалении: ' . $e->getMessage();
    }
}

$typeLabels = ['dinner' => 'Ужин', 'tasting' => 'Дегустация'];
$statusLabels = [
    'pending'   => 'Ожидает оплаты',
    'confirmed' => 'Подтверждена',
    'paid'      => 'Оплачена',
    'cancelled' => 'Отменена',
    'refunded'  => 'Возврат',
    'expired'   => 'Просрочена',
];

$pageTitle = 'Удаление события';
require __DIR__ . '/_layout_start.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
            <div><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width:720px; border:1px solid #e0a0a0; background:#fff8f8;">
    <h2 style="margin-top:0; color:#a83232;">Удалить событие?</h2>

    <p style="margin-bottom:16px;">
        <strong><?= htmlspecialchars($event['title']) ?></strong><br>
        <span style="color:#666; font-size:13px;">
            <?= $typeLabels[$event['type']] ?? htmlspecialchars($event['type']) ?> ·
            <?= htmlspecialchars($event['event_date']) ?>
            <?= htmlspecialchars(substr($event['event_time'], 0, 5)) ?> ·
            <?= (int)$event['booked'] ?>/<?= (int)$event['capacity'] ?> гостей
        </span>
    </p>

    <?php if (count($bookings) === 0): ?>
        <div class="alert" style="background:#f4f4f4; color:#333; padding:12px; border-radius:4px;">
            Связанных броней нет. Событие будет удалено без последствий.
        </div>
    <?php else: ?>
        <div class="alert alert-error" style="background:#fff0f0; color:#a83232; padding:14px; border-radius:4px; border:1px solid #e0a0a0;">
            <strong>⚠ Внимание!</strong>
            Вместе с событием будут <strong>безвозвратно удалены <?= count($bookings) ?> <?= count($bookings) === 1 ? 'бронь' : 'броней' ?></strong>.
            <?php if ($activeCount > 0): ?>
                <br><br>
                Из них <strong><?= $activeCount ?></strong>
                <?= $activeCount === 1 ? 'активная' : 'активных' ?>
                (pending/confirmed/paid) на <strong><?= $activeGuests ?></strong>
                <?= $activeGuests === 1 ? 'гостя' : 'гостей' ?>.
                <br>
                Гостям <u>не будет</u> отправлено уведомление, возврат оплаты <u>не будет</u> произведён автоматически.
                Свяжитесь с ними вручную до удаления.
            <?php endif; ?>
        </div>

        <h3 style="margin-top:24px; font-size:15px;">Брони, которые будут удалены:</h3>
        <table class="data-table" style="margin-top:8px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Контакт</th>
                    <th style="text-align:center;">Гостей</th>
                    <th>Статус</th>
                    <th>Создана</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= (int)$b['id'] ?></td>
                        <td><?= htmlspecialchars($b['name']) ?></td>
                        <td style="font-size:12px;">
                            <?= htmlspecialchars($b['phone']) ?><br>
                            <span style="color:#888;"><?= htmlspecialchars($b['email']) ?></span>
                        </td>
                        <td style="text-align:center;"><?= (int)$b['guests'] ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($b['status']) ?>"><?= $statusLabels[$b['status']] ?? htmlspecialchars($b['status']) ?></span></td>
                        <td style="font-size:12px; color:#666;"><?= htmlspecialchars($b['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <form method="post" style="margin-top:24px; display:flex; gap:12px; align-items:center;"
          onsubmit="return confirm('Точно удалить событие<?= count($bookings) > 0 ? ' и ' . count($bookings) . ' броней' : '' ?>? Действие необратимо.');">
        <input type="hidden" name="confirm" value="1">
        <button type="submit" class="btn" style="background:#a83232; color:#fff; border:none;">
            Да, удалить<?= count($bookings) > 0 ? ' событие и ' . count($bookings) . ' броней' : '' ?>
        </button>
        <a href="event-edit.php?id=<?= (int)$event['id'] ?>" class="btn btn-outline">Отмена</a>
    </form>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
