<?php
/**
 * Админка — Список событий
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

initAdminSession($config);
requireAdmin();

// Фильтры
$filterType   = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($filterType !== '' && in_array($filterType, ['dinner', 'tasting'], true)) {
    $where[] = 'type = ?';
    $params[] = $filterType;
}

if ($filterStatus !== '' && in_array($filterStatus, ['active', 'closed', 'sold_out'], true)) {
    $where[] = 'status = ?';
    $params[] = $filterStatus;
}

$sql = 'SELECT id, type, event_date, event_time, title, capacity, booked, price_per_guest, status
        FROM events';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY event_date DESC, event_time DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$typeLabels = ['dinner' => 'Ужин', 'tasting' => 'Дегустация'];
$statusLabels = ['active' => 'Активно', 'closed' => 'Закрыто', 'sold_out' => 'Распродано'];

$pageTitle = 'События';
require __DIR__ . '/_layout_start.php';
?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">
        Событие #<?= (int)$_GET['deleted'] ?> удалено.
        <?php if (isset($_GET['bookings']) && (int)$_GET['bookings'] > 0): ?>
            Вместе с ним удалено <?= (int)$_GET['bookings'] ?> броней.
        <?php else: ?>
            Связанных броней не было.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div></div>
    <a href="event-edit.php" class="btn btn-primary">+ Новое событие</a>
</div>

<form method="get" class="filters">
    <div class="form-group">
        <label>Тип</label>
        <select name="type" style="min-width:140px;">
            <option value="">Все</option>
            <option value="dinner" <?= $filterType === 'dinner' ? 'selected' : '' ?>>Ужин</option>
            <option value="tasting" <?= $filterType === 'tasting' ? 'selected' : '' ?>>Дегустация</option>
        </select>
    </div>
    <div class="form-group">
        <label>Статус</label>
        <select name="status" style="min-width:140px;">
            <option value="">Все</option>
            <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Активно</option>
            <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>Закрыто</option>
            <option value="sold_out" <?= $filterStatus === 'sold_out' ? 'selected' : '' ?>>Распродано</option>
        </select>
    </div>
    <div class="form-group">
        <label>&nbsp;</label>
        <button type="submit" class="btn btn-outline btn-sm">Фильтровать</button>
    </div>
</form>

<?php if (empty($events)): ?>
    <p style="color:#888;">Нет событий по заданным фильтрам.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Время</th>
                <th>Тип</th>
                <th>Название</th>
                <th style="text-align:center;">Места</th>
                <th>Цена</th>
                <th>Статус</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td><?= htmlspecialchars($ev['event_date']) ?></td>
                    <td><?= htmlspecialchars(substr($ev['event_time'], 0, 5)) ?></td>
                    <td><?= $typeLabels[$ev['type']] ?? htmlspecialchars($ev['type']) ?></td>
                    <td>
                        <a href="event-edit.php?id=<?= (int)$ev['id'] ?>" style="text-decoration:underline;">
                            <?= htmlspecialchars($ev['title']) ?>
                        </a>
                    </td>
                    <td style="text-align:center;"><?= (int)$ev['booked'] ?>/<?= (int)$ev['capacity'] ?></td>
                    <td style="white-space:nowrap;"><?= number_format((int)$ev['price_per_guest'] / 100, 0, ',', "\u{00a0}") ?> &#8381;</td>
                    <td><span class="badge badge-<?= htmlspecialchars($ev['status']) ?>"><?= $statusLabels[$ev['status']] ?? htmlspecialchars($ev['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
