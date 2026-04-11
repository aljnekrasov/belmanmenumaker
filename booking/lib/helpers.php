<?php
/**
 * Валидация, санитизация, CSRF, JSON-ответы, rate limiting
 */

// --- JSON-ответы ---

function jsonSuccess(array $data = []): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $code, string $message, array $fields = [], int $httpCode = 400): never
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    $error = ['code' => $code, 'message' => $message];
    if (!empty($fields)) {
        $error['fields'] = $fields;
    }
    echo json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- CSRF ---

function generateCsrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// --- Валидация ---

function sanitizeString(string $value): string
{
    return trim(htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone(string $phone): bool
{
    $cleaned = preg_replace('/[\s\-\(\)]+/', '', $phone);
    return (bool)preg_match('/^\+?[0-9]{10,15}$/', $cleaned);
}

function validateDate(string $date): bool
{
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
        && strtotime($date) !== false;
}

// --- Rate Limiting ---

function checkRateLimit(PDO $db, string $action, int $maxPerMinute): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Очистка старых записей (старше 2 минут)
    $db->prepare('DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE)')
        ->execute();

    // Подсчёт попыток
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM rate_limits WHERE ip = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)'
    );
    $stmt->execute([$ip, $action]);
    $count = (int)$stmt->fetchColumn();

    if ($count >= $maxPerMinute) {
        return false;
    }

    // Запись попытки
    $db->prepare('INSERT INTO rate_limits (ip, action) VALUES (?, ?)')
        ->execute([$ip, $action]);

    return true;
}

// --- Логирование ---

function logError(string $message): void
{
    $logDir = __DIR__ . '/../logs';
    $logFile = $logDir . '/error.log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// --- Чтение JSON-тела запроса ---

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        jsonError('INVALID_REQUEST', 'Некорректный формат запроса');
    }

    return $data;
}

// --- Форматирование денег ---

function kopecksToRubles(int $kopecks): string
{
    return number_format($kopecks / 100, 0, ',', ' ');
}

function formatPrice(int $kopecks): string
{
    return kopecksToRubles($kopecks) . ' ₽';
}
