<?php
/**
 * Исправление прав на файлы и папки.
 * УДАЛИТЬ ПОСЛЕ ИСПОЛЬЗОВАНИЯ!
 * Открыть: https://radius45.ru/fix-perms.php?key=fixradius2026
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'fixradius2026') {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$baseDir = __DIR__ . '/booking';
$dirs = 0;
$files = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    if ($item->isDir()) {
        chmod($item->getPathname(), 0755);
        $dirs++;
    } else {
        chmod($item->getPathname(), 0644);
        $files++;
    }
}

// И саму папку booking
chmod($baseDir, 0755);

echo "Готово!\n";
echo "Папок: {$dirs} (755)\n";
echo "Файлов: {$files} (644)\n";
echo "\nУДАЛИТЕ ЭТОТ ФАЙЛ!\n";
