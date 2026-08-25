<?php
declare(strict_types=1);

/*
 * ============================================================
 * SENTINEL32 DEVICE API AUTHENTICATION
 * ============================================================
 */

function requireDeviceAuth(): void
{
    $expectedKey = getenv('DEVICE_API_KEY');

    // --------------------------------------------------------
    // SERVER CONFIG CHECK
    // --------------------------------------------------------

    if ($expectedKey === false || trim($expectedKey) === '') {

        error_log(
            'Sentinel32 AUTH ERROR: DEVICE_API_KEY is not configured.'
        );

        http_response_code(500);

        header('Content-Type: application/json');

        echo json_encode([
            'ok'    => false,
            'error' => 'Server authentication configuration error'
        ]);

        exit;
    }

    $expectedKey = trim($expectedKey);

    // --------------------------------------------------------
    // READ X-API-KEY
    // --------------------------------------------------------

    $providedKey = '';

    // Method 1: Apache/PHP
    if (isset($_SERVER['HTTP_X_API_KEY'])) {

        $providedKey =
            trim((string)$_SERVER['HTTP_X_API_KEY']);
    }

    // Method 2: getallheaders fallback
    if (
        $providedKey === '' &&
        function_exists('getallheaders')
    ) {

        $headers = getallheaders();

        foreach ($headers as $name => $value) {

            if (
                strcasecmp(
                    (string)$name,
                    'X-API-Key'
                ) === 0
            ) {

                $providedKey =
                    trim((string)$value);

                break;
            }
        }
    }

    // --------------------------------------------------------
    // HEADER MISSING
    // --------------------------------------------------------

    if ($providedKey === '') {

        error_log(
            'Sentinel32 AUTH: X-API-Key header missing.'
        );

        http_response_code(401);

        header('Content-Type: application/json');

        echo json_encode([
            'ok'    => false,
            'error' => 'Unauthorized device'
        ]);

        exit;
    }

    // --------------------------------------------------------
    // VALIDATE
    // --------------------------------------------------------

    if (
        !hash_equals(
            $expectedKey,
            $providedKey
        )
    ) {

        error_log(
            'Sentinel32 AUTH: Invalid device API key.'
        );

        http_response_code(401);

        header('Content-Type: application/json');

        echo json_encode([
            'ok'    => false,
            'error' => 'Unauthorized device'
        ]);

        exit;
    }
}
