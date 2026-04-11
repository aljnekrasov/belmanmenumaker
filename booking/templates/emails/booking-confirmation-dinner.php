<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#FAF8F4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:4px;">

<tr><td style="background:#2F4A3F;padding:40px;text-align:center;">
  <h1 style="color:#C5A55A;margin:0;font-size:24px;">Радиус</h1>
  <p style="color:rgba(250,248,244,.7);margin:10px 0 0;font-size:14px;">Бронирование подтверждено</p>
</td></tr>

<tr><td style="padding:40px;">
  <h2 style="color:#2F4A3F;margin:0 0 20px;font-size:20px;">Здравствуйте, <?= htmlspecialchars($booking['name']) ?>!</h2>
  <p style="color:#555;font-size:15px;line-height:1.6;">Ваше бронирование на гастрономический ужин подтверждено и оплачено. Ждём вас!</p>

  <table width="100%" cellpadding="8" cellspacing="0" style="margin:24px 0;border-collapse:collapse;">
    <tr><td style="color:#7A8F7A;font-size:12px;text-transform:uppercase;letter-spacing:1px;padding:8px 0;border-bottom:1px solid #eee;">Событие</td>
        <td style="font-size:15px;font-weight:bold;padding:8px 0;border-bottom:1px solid #eee;"><?= htmlspecialchars($event['title'] ?? $event['event_title']) ?></td></tr>
    <tr><td style="color:#7A8F7A;font-size:12px;text-transform:uppercase;letter-spacing:1px;padding:8px 0;border-bottom:1px solid #eee;">Дата</td>
        <td style="font-size:15px;padding:8px 0;border-bottom:1px solid #eee;"><?= date('d.m.Y', strtotime($event['event_date'])) ?></td></tr>
    <tr><td style="color:#7A8F7A;font-size:12px;text-transform:uppercase;letter-spacing:1px;padding:8px 0;border-bottom:1px solid #eee;">Время</td>
        <td style="font-size:15px;padding:8px 0;border-bottom:1px solid #eee;"><?= substr($event['event_time'], 0, 5) ?></td></tr>
    <tr><td style="color:#7A8F7A;font-size:12px;text-transform:uppercase;letter-spacing:1px;padding:8px 0;border-bottom:1px solid #eee;">Гостей</td>
        <td style="font-size:15px;padding:8px 0;border-bottom:1px solid #eee;"><?= (int)$booking['guests'] ?></td></tr>
    <tr><td style="color:#7A8F7A;font-size:12px;text-transform:uppercase;letter-spacing:1px;padding:8px 0;border-bottom:1px solid #eee;">Сумма</td>
        <td style="font-size:15px;font-weight:bold;color:#2F4A3F;padding:8px 0;border-bottom:1px solid #eee;"><?= number_format((int)$booking['total_amount'] / 100, 0, ',', ' ') ?> ₽</td></tr>
  </table>

  <div style="background:#FAF8F4;padding:20px;border-radius:4px;margin:24px 0;">
    <p style="margin:0 0 8px;font-size:13px;color:#7A8F7A;text-transform:uppercase;letter-spacing:1px;">Адрес</p>
    <p style="margin:0;font-size:15px;color:#333;">Россия, Крым, Севастополь, село Родное<br>Винодельня Belmas</p>
  </div>

  <div style="background:#FAF8F4;padding:20px;border-radius:4px;margin:24px 0;">
    <p style="margin:0 0 8px;font-size:13px;color:#7A8F7A;text-transform:uppercase;letter-spacing:1px;">Рекомендации</p>
    <ul style="margin:0;padding:0 0 0 18px;font-size:14px;color:#555;line-height:1.8;">
      <li>Рекомендуем удобную закрытую обувь</li>
      <li>Вечером в горах бывает прохладно — возьмите тёплую одежду</li>
      <li>При пищевой аллергии предупредите заранее</li>
    </ul>
  </div>

  <p style="color:#555;font-size:14px;line-height:1.6;">Контакт для связи: <a href="mailto:hello@belmaswinery.ru" style="color:#2F4A3F;">hello@belmaswinery.ru</a></p>
</td></tr>

<tr><td style="background:#2F4A3F;padding:24px;text-align:center;">
  <p style="color:rgba(250,248,244,.5);margin:0;font-size:12px;">© <?= date('Y') ?> Ресторан «Радиус» при винодельне Belmas</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
