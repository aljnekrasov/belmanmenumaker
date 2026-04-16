<?php
/**
 * Админка — Список бронирований
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

initAdminSession($config);
requireAdmin();

// Фильтры
$filterStatus = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($filterStatus !== '' && in_array($filterStatus, ['pending', 'confirmed', 'paid', 'cancelled', 'refunded', 'expired'], true)) {
    $where[] = 'b.status = ?';
    $params[] = $filterStatus;
}

if ($search !== '') {
    $where[] = '(b.name LIKE ? OR b.email LIKE ? OR b.phone LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = 'SELECT b.id, b.name, b.guests, b.total_amount, b.status, b.created_at, e.title AS event_title
        FROM bookings b
        JOIN events e ON b.event_id = e.id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY b.created_at DESC LIMIT 500';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// CSV экспорт
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=bookings_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    // BOM for Excel
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Событие', 'Гость', 'Кол-во', 'Сумма (руб)', 'Статус', 'Создано'], ';');
    foreach ($bookings as $b) {
        fputcsv($out, [
            $b['id'],
            $b['event_title'],
            $b['name'],
            $b['guests'],
            number_format((int)$b['total_amount'] / 100, 2, ',', ''),
            $b['status'],
            $b['created_at'],
        ], ';');
    }
    fclose($out);
    exit;
}

$statusLabels = [
    'pending'   => 'Ожидание',
    'paid'      => 'Оплачено',
    'cancelled' => 'Отменено',
    'refunded'  => 'Возврат',
    'expired'   => 'Истекло',
];

$pageTitle = 'Бронирования';
require __DIR__ . '/_layout_start.php';
?>

<form method="get" class="filters">
    <div class="form-group">
        <label>Статус</label>
        <select name="status" style="min-width:140px;">
            <option value="">Все</option>
            <?php foreach ($statusLabels as $val => $label): ?>
                <option value="<?= $val ?>" <?= $filterStatus === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Поиск (имя / email / телефон)</label>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Введите запрос..." style="min-width:220px;">
    </div>
    <div class="form-group">
        <label>&nbsp;</label>
        <button type="submit" class="btn btn-outline btn-sm">Найти</button>
    </div>
    <div class="form-group">
        <label>&nbsp;</label>
        <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-gold btn-sm">Экспорт CSV</a>
    </div>
</form>

<?php if (empty($bookings)): ?>
    <p style="color:#888;">Нет бронирований по заданным фильтрам.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Событие</th>
                <th>Гость</th>
                <th style="text-align:center;">Кол-во</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Создано</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td>
                        <a href="booking-view.php?id=<?= (int)$b['id'] ?>" style="text-decoration:underline; font-family:'PT Mono',monospace;">
                            #<?= (int)$b['id'] ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($b['event_title']) ?></td>
                    <td><?= htmlspecialchars($b['name']) ?></td>
                    <td style="text-align:center;"><?= (int)$b['guests'] ?></td>
                    <td style="white-space:nowrap;"><?= number_format((int)$b['total_amount'] / 100, 0, ',', "\u{00a0}") ?> &#8381;</td>
                    <td><span class="badge badge-<?= htmlspecialchars($b['status']) ?>"><?= $statusLabels[$b['status']] ?? htmlspecialchars($b['status']) ?></span></td>
                    <td style="font-size:13px; color:#666;"><?= htmlspecialchars($b['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p style="margin-top:12px; font-size:13px; color:#888; font-family:'PT Mono',monospace;">
        Показано: <?= count($bookings) ?> записей
    </p>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
