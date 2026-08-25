<?php
declare(strict_types=1);

require_once __DIR__.'/../includes/functions.php';

requireDeviceKey();

$d = requestJson();

$deviceId = cleanText(
    $d['device_id'] ?? '',
    50
);

if ($deviceId === '') {
    jsonResponse([
        'ok' => false,
        'error' => 'device_id required'
    ], 422);
}

$stmt = db()->prepare(
    "INSERT INTO sensors(
        device_id,
        device_name,
        ip_address,
        firmware_version,
        status,
        last_seen
    )
    VALUES(
        ?, ?, ?, ?, 'ONLINE', CURRENT_TIMESTAMP
    )
    ON CONFLICT (device_id) DO UPDATE SET
        device_name = EXCLUDED.device_name,
        ip_address = EXCLUDED.ip_address,
        firmware_version = EXCLUDED.firmware_version,
        status = 'ONLINE',
        last_seen = CURRENT_TIMESTAMP"
);

$stmt->execute([
    $deviceId,

    cleanText(
        $d['device_name'] ?? 'ESP32 IDS',
        100
    ),

    cleanText(
        $d['ip_address'] ?? '',
        45
    ) ?: null,

    cleanText(
        $d['firmware'] ?? '',
        30
    ) ?: null
]);

jsonResponse([
    'ok' => true,
    'message' => 'Heartbeat received'
]);
