<?php
declare(strict_types=1);

/*
 * ============================================================
 * SENTINEL32 IoT IDS
 * Telemetry Receiver API
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
 */

$d = requestJson();


/*
 * ============================================================
 * AUTHENTICATE ESP32
 * ============================================================
 *
 * Authentication can use:
 *
 * X-API-Key header
 *
 * OR
 *
 * api_key inside JSON body
 */

requireDeviceKey($d);


/*
 * ============================================================
 * DEVICE INFORMATION
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
 * VALIDATION
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
 * TELEMETRY VALUES
 * ============================================================
 */

$packetCount =
    max(
        0,
        (int)($d['packet_count'] ?? 0)
    );


$pps =
    max(
        0,
        (float)($d['pps'] ?? 0)
    );


$managementFrames =
    max(
        0,
        (int)($d['management_frames'] ?? 0)
    );


$dataFrames =
    max(
        0,
        (int)($d['data_frames'] ?? 0)
    );


$controlFrames =
    max(
        0,
        (int)($d['control_frames'] ?? 0)
    );


$probeFrames =
    max(
        0,
        (int)($d['probe_frames'] ?? 0)
    );


$deauthFrames =
    max(
        0,
        (int)($d['deauth_frames'] ?? 0)
    );


$disassociationFrames =
    max(
        0,
        (int)($d['disassociation_frames'] ?? 0)
    );


$uniqueDevices =
    max(
        0,
        (int)($d['unique_devices'] ?? 0)
    );


$averageRSSI =
    (float)($d['average_rssi'] ?? 0);


$channel =
    max(
        0,
        (int)($d['channel'] ?? 0)
    );


/*
 * ============================================================
 * DATABASE
 * ============================================================
 */

$pdo = db();


try {

    /*
     * Start transaction.
     *
     * Sensor update + telemetry insertion should either
     * both succeed or both fail.
     */

    $pdo->beginTransaction();


    /*
     * ========================================================
     * UPDATE SENSOR
     * ========================================================
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


    $stmt->execute([
        $deviceId,
        $deviceName,
        $ipAddress !== '' ? $ipAddress : null,
        $firmware !== '' ? $firmware : null
    ]);


    /*
     * ========================================================
     * INSERT TELEMETRY
     * ========================================================
     */

    $stmt = $pdo->prepare(
        "
        INSERT INTO telemetry (
            device_id,
            packet_count,
            packets_per_second,
            management_frames,
            data_frames,
            control_frames,
            probe_frames,
            deauth_frames,
            disassociation_frames,
            unique_devices,
            avg_rssi,
            channel_number
        )

        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
        "
    );


    $stmt->execute([

        $deviceId,

        $packetCount,

        $pps,

        $managementFrames,

        $dataFrames,

        $controlFrames,

        $probeFrames,

        $deauthFrames,

        $disassociationFrames,

        $uniqueDevices,

        $averageRSSI,

        $channel
    ]);


    /*
     * ========================================================
     * COMMIT
     * ========================================================
     */

    $pdo->commit();


    /*
     * ========================================================
     * SUCCESS RESPONSE
     * ========================================================
     */

    jsonResponse([
        'ok' => true,
        'message' => 'Telemetry stored',
        'device_id' => $deviceId,
        'pps' => $pps,
        'server_time' => gmdate('c')
    ]);

}


/*
 * ============================================================
 * ERROR
 * ============================================================
 */

catch (Throwable $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    error_log(
        'Sentinel32 Telemetry DB error: ' .
        $e->getMessage()
    );


    jsonResponse([
        'ok' => false,
        'error' => 'Database error'
    ], 500);
}
