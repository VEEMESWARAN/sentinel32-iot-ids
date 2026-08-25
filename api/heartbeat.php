<?php
declare(strict_types=1);

/*
 * ============================================================
 * SENTINEL32 IoT IDS
 * Heartbeat API
 * ============================================================
 */

require_once __DIR__ . '/../includes/functions.php';


/*
 * ============================================================
 * ONLY ALLOW POST
 * ============================================================
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    jsonResponse([
        'ok' => false,
        'error' => 'Method not allowed'
    ], 405);
}


/*
 * ============================================================
 * READ JSON FIRST
 * ============================================================
 *
 * Important:
 * The JSON must be read before requireDeviceKey()
 * because api_key can be used as an authentication fallback.
 */

$d = requestJson();


/*
 * ============================================================
 * AUTHENTICATE ESP32
 * ============================================================
 */

requireDeviceKey($d);


/*
 * ============================================================
 * GET DEVICE INFORMATION
 * ============================================================
 */

$deviceId = cleanText(
    $d['device_id'] ?? '',
    50
);

$deviceName = cleanText(
    $d['device_name'] ?? 'Sentinel32 ESP32 Gateway',
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
 * ============================================================
 * VALIDATE DEVICE ID
 * ============================================================
 */

if ($deviceId === '') {

    jsonResponse([
        'ok' => false,
        'error' => 'device_id required'
    ], 422);
}


/*
 * ============================================================
 * DATABASE
 * ============================================================
 */

try {

    $pdo = db();


    /*
     * Create the device if it does not exist.
     *
     * Otherwise update:
     *
     * device name
     * IP
     * firmware
     * status
     * last seen
     */

    $stmt = $pdo->prepare(
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

            device_name =
                EXCLUDED.device_name,

            ip_address =
                EXCLUDED.ip_address,

            firmware_version =
                EXCLUDED.firmware_version,

            status =
                'ONLINE',

            last_seen =
                CURRENT_TIMESTAMP
        "
    );


    /*
     * Execute query
     */

    $stmt->execute([
        $deviceId,
        $deviceName,
        $ipAddress !== '' ? $ipAddress : null,
        $firmware !== '' ? $firmware : null
    ]);


    /*
     * ========================================================
     * SUCCESS
     * ========================================================
     */

    jsonResponse([
        'ok' => true,
        'message' => 'Heartbeat received',
        'device_id' => $deviceId,
        'status' => 'ONLINE',
        'server_time' => gmdate('c')
    ]);

}


/*
 * ============================================================
 * DATABASE ERROR
 * ============================================================
 */

catch (Throwable $e) {

    error_log(
        'Sentinel32 Heartbeat DB error: ' .
        $e->getMessage()
    );


    jsonResponse([
        'ok' => false,
        'error' => 'Heartbeat processing failed'
    ], 500);
}
