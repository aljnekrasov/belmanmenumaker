<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#FAF8F4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:4px;">

<tr><td style="background:#2F4A3F;padding:30px;text-align:center;">
  <h1 style="color:#C5A55A;margin:0;font-size:20px;">Новая оплаченная бронь</h1>
</td></tr>

<tr><td style="padding:30px;">
  <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">
    <tr><td style="color:#999;font-size:12px;width:120px;">Бронь №</td>
        <td style="font-size:14px;font-weight:bold;"><?= (int)$booking['id'] ?></td></tr>
    <tr><td style="color:#999;font-size:12px;">Событие</td>
        <td style="font-size:14px;"><?= htmlspecialchars($event['title'] ?? $event['event_title']) ?></td></tr>
    <tr><td style="color:#999;font-size:12px;">Тип</td>
        <td style="font-size:14px;"><?= $event['type'] === 'dinner' ? 'Ужин' : 'Дегустация' ?></td></tr>
    <tr><td style="color:#999;font-size:12px;">Дата</td>
        <td style="font-size:14px;"><?= date('d.m.Y', strtotime($event['event_date'])) ?> в <?= substr($event['event_time'], 0, 5) ?></td></tr>
    <tr><td style="color:#999;font-size:12px;">Имя</td>
        <td style="font-size:14px;"><?= htmlspecialchars($booking['name']) ?></td></tr>
    <tr><td style="color:#999;font-size:12px;">Телефон</td>
        <td style="font-size:14px;"><?= htmlspecialchars($booking['phone']) ?></td></tr>
    <tr><td style="color:#999;font-size:12px;">Email</td>
        <td style="font-size:14px;"><?= htmlspecialchars($booking['email']) ?></td></tr>
    <tr><td style="color:#999;font-size:12px;">Гостей</td>
        <td style="font-size:14px;"><?= (int)$booking['guests'] ?></td></tr>
    <tr><td style="color:#999;font-size:12px;">Сумма</td>
        <td style="font-size:14px;font-weight:bold;"><?= number_format((int)$booking['total_amount'] / 100, 0, ',', ' ') ?> ₽</td></tr>
    <?php if (!empty($booking['comment'])): ?>
    <tr><td style="color:#999;font-size:12px;">Комментарий</td>
        <td style="font-size:14px;"><?= htmlspecialchars($booking['comment']) ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($booking['dietary'])): ?>
    <tr><td style="color:#999;font-size:12px;">Диета</td>
        <td style="font-size:14px;"><?= htmlspecialchars(is_string($booking['dietary']) ? $booking['dietary'] : json_encode($booking['dietary'], JSON_UNESCAPED_UNICODE)) ?></td></tr>
    <?php endif; ?>
  </table>

  <p style="margin:20px 0 0;text-align:center;">
    <a href="<?= htmlspecialchars($config['booking_base_url']) ?>/admin/booking-view.php?id=<?= (int)$booking['id'] ?>"
       style="display:inline-block;padding:10px 24px;background:#2F4A3F;color:#C5A55A;text-decoration:none;border-radius:3px;font-size:13px;">
      Посмотреть в админке
    </a>
  </p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
