<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

requireDeviceAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'error' => 'Method not allowed'
    ]);

    exit;
}

try {

    $data =
        json_decode(
            file_get_contents('php://input'),
            true
        );

    if (!is_array($data)) {

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'error' => 'Invalid JSON'
        ]);

        exit;
    }

    $deviceId =
        trim(
            (string)($data['device_id'] ?? '')
        );

    if ($deviceId === '') {

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'error' => 'device_id required'
        ]);

        exit;
    }

    // ========================================================
    // UPDATE SENSOR
    // ========================================================

    $sensorSQL = "
        INSERT INTO sensors (
            device_id,
            device_name,
            ip_address,
            mac_address,
            firmware_version,
            status,
            last_seen
        )
        VALUES (
            :device_id,
            :device_name,
            :ip_address,
            :mac_address,
            :firmware,
            'ONLINE',
            CURRENT_TIMESTAMP
        )

        ON CONFLICT (device_id)

        DO UPDATE SET
            device_name =
                EXCLUDED.device_name,

            ip_address =
                EXCLUDED.ip_address,

            mac_address =
                EXCLUDED.mac_address,

            firmware_version =
                EXCLUDED.firmware_version,

            status =
                'ONLINE',

            last_seen =
                CURRENT_TIMESTAMP
    ";

    $stmt =
        $pdo->prepare(
            $sensorSQL
        );

    $stmt->execute([
        ':device_id' =>
            $deviceId,

        ':device_name' =>
            $data['device_name']
            ?? 'Sentinel32 ESP32 Gateway',

        ':ip_address' =>
            $data['ip_address']
            ?? null,

        ':mac_address' =>
            $data['mac_address']
            ?? null,

        ':firmware' =>
            $data['firmware']
            ?? null
    ]);

    // ========================================================
    // INSERT TELEMETRY
    // ========================================================

    $telemetrySQL = "
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
            average_rssi,
            wifi_channel,
            recorded_at
        )

        VALUES (
            :device_id,
            :packet_count,
            :pps,
            :management_frames,
            :data_frames,
            :control_frames,
            :probe_frames,
            :deauth_frames,
            :disassociation_frames,
            :unique_devices,
            :average_rssi,
            :wifi_channel,
            CURRENT_TIMESTAMP
        )
    ";

    $stmt =
        $pdo->prepare(
            $telemetrySQL
        );

    $stmt->execute([
        ':device_id' =>
            $deviceId,

        ':packet_count' =>
            (int)($data['packet_count'] ?? 0),

        ':pps' =>
            (float)($data['pps'] ?? 0),

        ':management_frames' =>
            (int)($data['management_frames'] ?? 0),

        ':data_frames' =>
            (int)($data['data_frames'] ?? 0),

        ':control_frames' =>
            (int)($data['control_frames'] ?? 0),

        ':probe_frames' =>
            (int)($data['probe_frames'] ?? 0),

        ':deauth_frames' =>
            (int)($data['deauth_frames'] ?? 0),

        ':disassociation_frames' =>
            (int)($data['disassociation_frames'] ?? 0),

        ':unique_devices' =>
            (int)($data['unique_devices'] ?? 0),

        ':average_rssi' =>
            (int)($data['average_rssi'] ?? 0),

        ':wifi_channel' =>
            (int)($data['channel'] ?? 0)
    ]);

    echo json_encode([
        'ok' => true,
        'message' =>
            'Telemetry received',
        'device_id' =>
            $deviceId
    ]);

}
catch (Throwable $e) {

    error_log(
        'Telemetry DB error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => 'Database error'
    ]);
}
