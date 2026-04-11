# Система бронирования «Радиус»

Встраиваемая система бронирования для ресторана «Радиус» при винодельне Belmas.

## Стек

- PHP 8.1+ (нативный, без фреймворков)
- MySQL 5.7+ / MariaDB 10.3+
- PHPMailer для email
- Ванильный JS на фронте

## Установка

### 1. База данных

Создайте БД и пользователя в панели хостинга. Импортируйте схему:

```bash
mysql -u USER -p DB_NAME < migrations/001-init.sql
```

Или через phpMyAdmin: импортируйте файл `migrations/001-init.sql`.

### 2. Конфигурация

```bash
cp config.example.php config.php
```

Заполните реальные значения в `config.php`:
- Подключение к БД
- SMTP-настройки для email
- URL сайта

### 3. Зависимости

Если Composer доступен:
```bash
cd booking
composer install
```

Если нет — загрузите `vendor/` с готовой сборки.

### 4. Создание администратора

Через SSH:
```bash
php migrations/create-admin.php admin "Имя Фамилия" "пароль"
```

**После создания удалите файл `migrations/create-admin.php`.**

### 5. Cron

В панели хостинга настройте запуск каждые 5 минут:
```
*/5 * * * * /usr/bin/php /home/USER/public_html/booking/cron/cleanup-pending.php
```

### 6. SSL

Включите Let's Encrypt — обязательно для работы secure cookies.

## Деплой на reg.ru

1. Создать БД и пользователя в панели reg.ru
2. Залить проект по FTP в `/public_html/booking/`
3. Скопировать `config.example.php` → `config.php`, заполнить
4. Применить миграцию через phpMyAdmin
5. Создать админа через CLI или временный скрипт
6. Настроить cron в панели reg.ru
7. Включить SSL (Let's Encrypt)
8. Проверить отправку тестового email
9. Создать тестовое событие, пройти полный сценарий
10. Удалить `migrations/create-admin.php`

## Структура проекта

```
booking/
├── api/                    # API-эндпоинты
│   ├── get-availability.php    GET — доступные даты/слоты
│   ├── create-booking.php      POST — создание брони
│   ├── confirm-payment.php     GET — подтверждение оплаты (заглушка)
│   └── payment-webhook.php     POST — webhook платёжки (каркас)
├── admin/                  # Админка
├── lib/                    # Библиотеки
│   ├── bootstrap.php           Инициализация
│   ├── config.php              Загрузка конфига
│   ├── db.php                  PDO-подключение
│   ├── helpers.php             Утилиты
│   ├── auth.php                Авторизация
│   ├── booking-service.php     Бизнес-логика
│   ├── mailer.php              Email
│   └── payment/                Платёжные провайдеры
├── templates/emails/       # Шаблоны писем
├── public/form-snippets/   # Формы для встраивания
├── cron/                   # Фоновые задачи
├── migrations/             # Схема БД
└── logs/                   # Логи
```

## API

### GET /booking/api/get-availability.php

| Параметр | Тип | Описание |
|----------|-----|----------|
| type | string | `dinner` или `tasting` (обязательный) |
| date | string | `YYYY-MM-DD` (опциональный) |

Без `date` — список дат. С `date` — слоты на эту дату.

### POST /booking/api/create-booking.php

```json
{
  "event_id": 12,
  "guests": 2,
  "name": "Имя",
  "phone": "+79991234567",
  "email": "user@example.com",
  "comment": "",
  "dietary": ["vegetarian"],
  "agree_terms": true,
  "agree_privacy": true
}
```

### GET /booking/api/confirm-payment.php?token=...

Страница подтверждения оплаты (заглушка).

## База данных

- **events** — события (ужины, дегустации)
- **bookings** — бронирования
- **admin_users** — администраторы
- **rate_limits** — защита от спама

Все суммы хранятся в **копейках**.

## Встраивание на лендинг

Подключите CSS и JS в `<head>`:
```html
<link rel="stylesheet" href="/booking/public/form-snippets/booking.css">
<script src="/booking/public/form-snippets/booking.js" defer></script>
```

Вставьте форму в нужное место страницы:
```html
<!-- Для ужина -->
<div id="dinner-booking" class="rb-form" data-type="dinner" data-api="/booking/api"></div>

<!-- Для дегустации -->
<div id="tasting-booking" class="rb-form" data-type="tasting" data-api="/booking/api"></div>
```

## Подключение ЮKassa (будущее)

1. Создать класс `YooKassaPaymentProvider` реализующий `PaymentProvider`
2. Разместить в `lib/payment/`
3. В `config.php` изменить `'payment_provider' => 'yookassa'`
4. Добавить конфиг ЮKassa в `config.php`
5. Реализовать обработку webhook в `payment-webhook.php`
6. Удалить или отключить `confirm-payment.php`

Бизнес-логика в `BookingService` не потребует изменений.
