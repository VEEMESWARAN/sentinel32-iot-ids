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

$pdo = db();

$pdo->beginTransaction();

try {

    // =========================================================
    // SENSOR STATUS
    // =========================================================

    $stmt = $pdo->prepare(
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

    // =========================================================
    // TELEMETRY
    // =========================================================

    $stmt = $pdo->prepare(
        "INSERT INTO telemetry(
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
        VALUES(
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )"
    );

    $stmt->execute([
        $deviceId,

        (int)($d['packet_count'] ?? 0),

        (float)($d['pps'] ?? 0),

        (int)($d['management_frames'] ?? 0),

        (int)($d['data_frames'] ?? 0),

        (int)($d['control_frames'] ?? 0),

        (int)($d['probe_frames'] ?? 0),

        (int)($d['deauth_frames'] ?? 0),

        (int)($d['disassociation_frames'] ?? 0),

        (int)($d['unique_devices'] ?? 0),

        (float)($d['average_rssi'] ?? 0),

        (int)($d['channel'] ?? 0)
    ]);

    $pdo->commit();

    jsonResponse([
        'ok' => true,
        'message' => 'Telemetry stored'
    ]);

}
catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Telemetry DB error: ' .
        $e->getMessage()
    );

    jsonResponse([
        'ok' => false,
        'error' => 'Database error'
    ], 500);
}
