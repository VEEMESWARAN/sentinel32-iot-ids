<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = db();
    $pdo->query('SELECT 1');

    echo json_encode([
        'ok' => true,
        'app' => 'Sentinel32 IoT IDS',
        'database' => 'connected',
        'time' => gmdate('c')
    ], JSON_UNESCAPED_SLASHES);
}
catch (Throwable $e) {
    error_log('Health DB error: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'app' => 'Sentinel32 IoT IDS',
        'database' => 'error'
    ], JSON_UNESCAPED_SLASHES);
}
