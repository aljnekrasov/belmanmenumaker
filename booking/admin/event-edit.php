<?php
/**
 * Админка — Создание / редактирование события
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

initAdminSession($config);
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$event = null;
$errors = [];
$success = '';

// Загрузка существующего события
if ($isEdit) {
    $stmt = $db->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $event = $stmt->fetch();
    if (!$event) {
        header('Location: events.php');
        exit;
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $priceRub = $_POST['price_per_guest'] ?? '';
    $durationMinutes = $_POST['duration_minutes'] ?? '';
    $status = $_POST['status'] ?? 'active';

    // Валидация
    if (!in_array($type, ['dinner', 'tasting'], true)) {
        $errors[] = 'Выберите тип события';
    }
    if ($eventDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
        $errors[] = 'Укажите корректную дату';
    }
    if ($eventTime === '' || !preg_match('/^\d{2}:\d{2}$/', $eventTime)) {
        $errors[] = 'Укажите корректное время';
    }
    if ($title === '') {
        $errors[] = 'Введите название';
    }
    if ($capacity < 1) {
        $errors[] = 'Вместимость должна быть больше 0';
    }
    if (!is_numeric($priceRub) || (float)$priceRub < 0) {
        $errors[] = 'Укажите корректную цену';
    }
    if (!in_array($status, ['active', 'closed', 'sold_out'], true)) {
        $errors[] = 'Некорректный статус';
    }

    $priceKopecks = (int)round((float)$priceRub * 100);
    $durationMin = ($durationMinutes !== '' && is_numeric($durationMinutes)) ? (int)$durationMinutes : null;

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $db->prepare(
                'UPDATE events SET type=?, event_date=?, event_time=?, title=?, description=?, capacity=?, price_per_guest=?, duration_minutes=?, status=?
                 WHERE id=?'
            );
            $stmt->execute([$type, $eventDate, $eventTime, $title, $description, $capacity, $priceKopecks, $durationMin, $status, $id]);
            $success = 'Событие обновлено';
            // Reload
            $stmt = $db->prepare('SELECT * FROM events WHERE id = ?');
            $stmt->execute([$id]);
            $event = $stmt->fetch();
        } else {
            $stmt = $db->prepare(
                'INSERT INTO events (type, event_date, event_time, title, description, capacity, price_per_guest, duration_minutes, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$type, $eventDate, $eventTime, $title, $description, $capacity, $priceKopecks, $durationMin, $status]);
            $newId = (int)$db->lastInsertId();
            header('Location: event-edit.php?id=' . $newId . '&created=1');
            exit;
        }
    }
}

// Показать сообщение после создания
if (isset($_GET['created'])) {
    $success = 'Событие создано';
}

// Значения для формы
$f = [
    'type'             => $_POST['type'] ?? ($event['type'] ?? 'dinner'),
    'event_date'       => $_POST['event_date'] ?? ($event['event_date'] ?? ''),
    'event_time'       => $_POST['event_time'] ?? ($event ? substr($event['event_time'], 0, 5) : ''),
    'title'            => $_POST['title'] ?? ($event['title'] ?? ''),
    'description'      => $_POST['description'] ?? ($event['description'] ?? ''),
    'capacity'         => $_POST['capacity'] ?? ($event['capacity'] ?? ''),
    'price_per_guest'  => $_POST['price_per_guest'] ?? ($event ? (int)$event['price_per_guest'] / 100 : ''),
    'duration_minutes' => $_POST['duration_minutes'] ?? ($event['duration_minutes'] ?? ''),
    'status'           => $_POST['status'] ?? ($event['status'] ?? 'active'),
];

$pageTitle = $isEdit ? 'Редактирование события' : 'Новое событие';
require __DIR__ . '/_layout_start.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
            <div><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width:640px;">
    <form method="post">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 20px;">
            <div class="form-group">
                <label for="type">Тип</label>
                <select id="type" name="type" required>
                    <option value="dinner" <?= $f['type'] === 'dinner' ? 'selected' : '' ?>>Ужин</option>
                    <option value="tasting" <?= $f['type'] === 'tasting' ? 'selected' : '' ?>>Дегустация</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Статус</label>
                <select id="status" name="status" required>
                    <option value="active" <?= $f['status'] === 'active' ? 'selected' : '' ?>>Активно</option>
                    <option value="closed" <?= $f['status'] === 'closed' ? 'selected' : '' ?>>Закрыто</option>
                    <option value="sold_out" <?= $f['status'] === 'sold_out' ? 'selected' : '' ?>>Распродано</option>
                </select>
            </div>
            <div class="form-group">
                <label for="event_date">Дата</label>
                <input type="date" id="event_date" name="event_date" required
                       value="<?= htmlspecialchars($f['event_date']) ?>">
            </div>
            <div class="form-group">
                <label for="event_time">Время</label>
                <input type="time" id="event_time" name="event_time" required
                       value="<?= htmlspecialchars($f['event_time']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="title">Название</label>
            <input type="text" id="title" name="title" required
                   value="<?= htmlspecialchars($f['title']) ?>">
        </div>

        <div class="form-group">
            <label for="description">Описание</label>
            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($f['description']) ?></textarea>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0 20px;">
            <div class="form-group">
                <label for="capacity">Вместимость</label>
                <input type="number" id="capacity" name="capacity" min="1" required
                       value="<?= htmlspecialchars((string)$f['capacity']) ?>">
            </div>
            <div class="form-group">
                <label for="price_per_guest">Цена, &#8381;</label>
                <input type="number" id="price_per_guest" name="price_per_guest" min="0" step="1" required
                       value="<?= htmlspecialchars((string)$f['price_per_guest']) ?>">
            </div>
            <div class="form-group">
                <label for="duration_minutes">Длительность, мин</label>
                <input type="number" id="duration_minutes" name="duration_minutes" min="1"
                       value="<?= htmlspecialchars((string)$f['duration_minutes']) ?>">
            </div>
        </div>

        <div style="display:flex; gap:12px; margin-top:8px; align-items:center;">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Сохранить' : 'Создать' ?></button>
            <a href="events.php" class="btn btn-outline">Отмена</a>
            <?php if ($isEdit): ?>
                <a href="event-delete.php?id=<?= (int)$event['id'] ?>"
                   class="btn"
                   style="margin-left:auto; background:#fff; color:#a83232; border:1px solid #d9a0a0;">
                    Удалить событие
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
    <div style="margin-top:12px;">
        <p style="font-size:13px; color:#888; font-family:'PT Mono',monospace;">
            ID: <?= (int)$event['id'] ?> &middot;
            Забронировано: <?= (int)$event['booked'] ?>/<?= (int)$event['capacity'] ?> &middot;
            Создано: <?= htmlspecialchars($event['created_at']) ?>
        </p>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
