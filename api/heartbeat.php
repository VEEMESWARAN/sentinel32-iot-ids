<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

/*
|--------------------------------------------------------------------------
| READ REQUEST
|--------------------------------------------------------------------------
*/

$d = requestJson();

/*
|--------------------------------------------------------------------------
| AUTHENTICATE DEVICE
|--------------------------------------------------------------------------
|
| Pass JSON data so requireDeviceKey() can check:
|
| 1. X-API-Key header
| 2. api_key JSON fallback
|
*/

requireDeviceKey($d);


/*
|--------------------------------------------------------------------------
| DEVICE DATA
|--------------------------------------------------------------------------
*/

$deviceId = cleanText(
    $d['device_id'] ?? '',
    50
);

$deviceName = cleanText(
    $d['device_name'] ?? 'ESP32 IDS',
    100
);

$ipAddress = cleanText(
    $d['ip_address'] ?? '',
    45
);

$firmware = cleanText(
    $d['firmware'] ?? '',
    30
);


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($deviceId === '') {

    jsonResponse([
        'ok' => false,
        'error' => 'device_id required'
    ], 422);
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    $stmt = db()->prepare(
        "
        INSERT INTO sensors (
            device_id,
            device_name,
            ip_address,
            firmware_version,
            status,
            last_seen
        )

        VALUES (
            ?,
            ?,
            ?,
            ?,
            'ONLINE',
            CURRENT_TIMESTAMP
        )

        ON CONFLICT (device_id)

        DO UPDATE SET
            device_name = EXCLUDED.device_name,
            ip_address = EXCLUDED.ip_address,
            firmware_version = EXCLUDED.firmware_version,
            status = 'ONLINE',
            last_seen = CURRENT_TIMESTAMP
        "
    );


    $stmt->execute([
        $deviceId,
        $deviceName,
        $ipAddress !== '' ? $ipAddress : null,
        $firmware !== '' ? $firmware : null
    ]);


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    jsonResponse([
        'ok' => true,
        'message' => 'Heartbeat received',
        'device_id' => $deviceId,
        'status' => 'ONLINE',
        'server_time' => gmdate('c')
    ]);

}
catch (Throwable $e) {

    error_log(
        'Heartbeat DB error: ' .
        $e->getMessage()
    );


    jsonResponse([
        'ok' => false,
        'error' => 'Heartbeat database error'
    ], 500);
}
