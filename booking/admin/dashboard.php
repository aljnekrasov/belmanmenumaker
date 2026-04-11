<?php
/**
 * Админка — Дашборд
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

initAdminSession($config);
requireAdmin();

// Активные события
$stmt = $db->query("SELECT COUNT(*) FROM events WHERE status = 'active' AND event_date >= CURDATE()");
$activeEvents = (int) $stmt->fetchColumn();

// Бронирования за 7 дней
$stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$bookings7days = (int) $stmt->fetchColumn();

// Оплаченные за 7 дней
$stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$paid7days = (int) $stmt->fetchColumn();

// Выручка за месяц (kopecks -> roubles)
$stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status = 'paid' AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$revenueKopecks = (int) $stmt->fetchColumn();
$revenueRub = $revenueKopecks / 100;

// Всего гостей за месяц
$stmt = $db->query("SELECT COALESCE(SUM(guests), 0) FROM bookings WHERE status = 'paid' AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$guestsMonth = (int) $stmt->fetchColumn();

// Ближайшие 5 событий
$stmt = $db->query(
    "SELECT id, type, event_date, event_time, title, booked, capacity, status
     FROM events
     WHERE event_date >= CURDATE()
     ORDER BY event_date, event_time
     LIMIT 5"
);
$upcomingEvents = $stmt->fetchAll();

$pageTitle = 'Дашборд';
require __DIR__ . '/_layout_start.php';
?>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:32px;">
    <div style="background:#fff; border-radius:10px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <div style="font-size:13px; color:#888; margin-bottom:4px; font-family:'PT Mono',monospace;">Активные события</div>
        <div style="font-size:32px; font-weight:700; color:var(--green);"><?= $activeEvents ?></div>
    </div>
    <div style="background:#fff; border-radius:10px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <div style="font-size:13px; color:#888; margin-bottom:4px; font-family:'PT Mono',monospace;">Бронирований за 7 дней</div>
        <div style="font-size:32px; font-weight:700; color:var(--green);"><?= $bookings7days ?></div>
        <div style="font-size:12px; color:#888;">из них оплачено: <?= $paid7days ?></div>
    </div>
    <div style="background:#fff; border-radius:10px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <div style="font-size:13px; color:#888; margin-bottom:4px; font-family:'PT Mono',monospace;">Выручка за месяц</div>
        <div style="font-size:32px; font-weight:700; color:var(--gold);"><?= number_format($revenueRub, 0, ',', "\u{00a0}") ?> &#8381;</div>
    </div>
    <div style="background:#fff; border-radius:10px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <div style="font-size:13px; color:#888; margin-bottom:4px; font-family:'PT Mono',monospace;">Гостей за месяц</div>
        <div style="font-size:32px; font-weight:700; color:var(--green);"><?= $guestsMonth ?></div>
    </div>
</div>

<h2 style="font-size:18px; color:var(--green); margin-bottom:16px;">Ближайшие события</h2>

<?php if (empty($upcomingEvents)): ?>
    <p style="color:#888;">Нет предстоящих событий.</p>
<?php else: ?>
    <table style="width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <thead>
            <tr style="background:var(--green); color:var(--cream);">
                <th style="padding:10px 14px; text-align:left; font-size:13px;">Дата</th>
                <th style="padding:10px 14px; text-align:left; font-size:13px;">Время</th>
                <th style="padding:10px 14px; text-align:left; font-size:13px;">Тип</th>
                <th style="padding:10px 14px; text-align:left; font-size:13px;">Название</th>
                <th style="padding:10px 14px; text-align:center; font-size:13px;">Места</th>
                <th style="padding:10px 14px; text-align:left; font-size:13px;">Статус</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($upcomingEvents as $i => $ev): ?>
                <?php
                    $typeName = $ev['type'] === 'dinner' ? 'Ужин' : 'Дегустация';
                    $statusMap = ['active' => 'Активно', 'closed' => 'Закрыто', 'sold_out' => 'Распродано'];
                    $statusLabel = $statusMap[$ev['status']] ?? $ev['status'];
                    $bgColor = $i % 2 === 0 ? '#fff' : '#faf9f6';
                ?>
                <tr style="background:<?= $bgColor ?>; border-bottom:1px solid #eee;">
                    <td style="padding:10px 14px; font-size:14px;"><?= htmlspecialchars($ev['event_date']) ?></td>
                    <td style="padding:10px 14px; font-size:14px;"><?= htmlspecialchars(substr($ev['event_time'], 0, 5)) ?></td>
                    <td style="padding:10px 14px; font-size:14px;"><?= $typeName ?></td>
                    <td style="padding:10px 14px; font-size:14px;">
                        <a href="event-edit.php?id=<?= (int)$ev['id'] ?>" style="color:var(--green); text-decoration:underline;">
                            <?= htmlspecialchars($ev['title']) ?>
                        </a>
                    </td>
                    <td style="padding:10px 14px; font-size:14px; text-align:center;"><?= (int)$ev['booked'] ?>/<?= (int)$ev['capacity'] ?></td>
                    <td style="padding:10px 14px; font-size:14px;"><?= $statusLabel ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
