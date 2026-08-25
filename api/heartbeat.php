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

    $input =
        json_decode(
            file_get_contents('php://input'),
            true
        );

    if (!is_array($input)) {

        throw new RuntimeException(
            'Invalid JSON payload'
        );
    }

    $deviceId =
        trim(
            (string)($input['device_id'] ?? '')
        );

    $ipAddress =
        trim(
            (string)($input['ip_address'] ?? '')
        );

    $firmware =
        trim(
            (string)($input['firmware'] ?? '')
        );

    if ($deviceId === '') {

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'error' => 'device_id required'
        ]);

        exit;
    }

    /*
     * Create/update sensor.
     */

    $sql = "
        INSERT INTO sensors (
            device_id,
            device_name,
            ip_address,
            firmware_version,
            status,
            last_seen
        )
        VALUES (
            :device_id,
            :device_name,
            :ip_address,
            :firmware,
            'ONLINE',
            CURRENT_TIMESTAMP
        )

        ON CONFLICT (device_id)

        DO UPDATE SET
            ip_address = EXCLUDED.ip_address,
            firmware_version = EXCLUDED.firmware_version,
            status = 'ONLINE',
            last_seen = CURRENT_TIMESTAMP
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':device_id' =>
            $deviceId,

        ':device_name' =>
            'Sentinel32 ESP32 Gateway',

        ':ip_address' =>
            $ipAddress,

        ':firmware' =>
            $firmware
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'Heartbeat received',
        'device_id' => $deviceId,
        'status' => 'ONLINE',
        'server_time' =>
            gmdate('c')
    ]);

}
catch (Throwable $e) {

    error_log(
        'Heartbeat error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => 'Heartbeat processing failed'
    ]);
}
