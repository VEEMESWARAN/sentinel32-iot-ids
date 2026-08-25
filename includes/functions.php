<?php
declare(strict_types=1);

/*
 * ============================================================
 * SENTINEL32
 * Common Functions
 * ============================================================
 */

require_once __DIR__ . '/config.php';


/*
 * ============================================================
 * JSON RESPONSE
 * ============================================================
 */

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
 * ============================================================
 * READ JSON REQUEST
 * ============================================================
 */

function requestJson(): array
{
    $raw =
        file_get_contents('php://input') ?: '';

    $data =
        json_decode(
            $raw,
            true
        );

    if (!is_array($data)) {

        jsonResponse([
            'ok' => false,
            'error' => 'Invalid JSON body'
        ], 400);
    }

    return $data;
}


/*
 * ============================================================
 * DEVICE API AUTHENTICATION
 * ============================================================
 *
 * ESP32 sends:
 *
 * X-API-Key: YOUR_DEVICE_API_KEY
 *
 * Server reads the expected value using:
 *
 * deviceApiKey()
 *
 * from config.php.
 *
 * ============================================================
 */

function requireDeviceKey(): void
{
    /*
     * Get expected API key from the existing
     * Sentinel32 configuration.
     */

    $expected =
        trim(
            (string)deviceApiKey()
        );


    /*
     * --------------------------------------------------------
     * CHECK SERVER CONFIGURATION
     * --------------------------------------------------------
     */

    if ($expected === '') {

        error_log(
            'Sentinel32 AUTH: DEVICE_API_KEY is empty.'
        );

        jsonResponse([
            'ok' => false,
            'error' => 'DEVICE_API_KEY missing on server'
        ], 500);
    }


    /*
     * --------------------------------------------------------
     * READ API KEY FROM REQUEST
     * --------------------------------------------------------
     */

    $key = '';


    /*
     * Method 1
     *
     * Apache normally converts:
     *
     * X-API-Key
     *
     * into:
     *
     * HTTP_X_API_KEY
     */

    if (
        isset($_SERVER['HTTP_X_API_KEY'])
    ) {

        $key =
            trim(
                (string)
                $_SERVER['HTTP_X_API_KEY']
            );
    }


    /*
     * --------------------------------------------------------
     * METHOD 2 - FALLBACK
     * --------------------------------------------------------
     *
     * If Apache/PHP does not populate
     * HTTP_X_API_KEY, check all HTTP headers.
     */

    if (
        $key === '' &&
        function_exists('getallheaders')
    ) {

        $headers =
            getallheaders();

        if (is_array($headers)) {

            foreach (
                $headers as
                $name => $value
            ) {

                if (
                    strcasecmp(
                        (string)$name,
                        'X-API-Key'
                    ) === 0
                ) {

                    $key =
                        trim(
                            (string)$value
                        );

                    break;
                }
            }
        }
    }


    /*
     * --------------------------------------------------------
     * HEADER NOT RECEIVED
     * --------------------------------------------------------
     */

    if ($key === '') {

        error_log(
            'Sentinel32 AUTH: X-API-Key header missing.'
        );

        jsonResponse([
            'ok' => false,
            'error' => 'X-API-Key header missing'
        ], 401);
    }


    /*
     * --------------------------------------------------------
     * API KEY DOES NOT MATCH
     * --------------------------------------------------------
     *
     * IMPORTANT:
     * Never return the actual API keys.
     *
     * Only return their lengths for debugging.
     */

    if (
        !hash_equals(
            $expected,
            $key
        )
    ) {

        error_log(
            'Sentinel32 AUTH: API key mismatch. ' .
            'Received length=' .
            strlen($key) .
            ', Expected length=' .
            strlen($expected)
        );

        jsonResponse([
            'ok' => false,
            'error' => 'API key mismatch',

            'received_length' =>
                strlen($key),

            'expected_length' =>
                strlen($expected)
        ], 401);
    }


    /*
     * --------------------------------------------------------
     * AUTHENTICATION SUCCESSFUL
     * --------------------------------------------------------
     *
     * Do nothing.
     *
     * The calling API endpoint will continue
     * executing normally.
     */

    error_log(
        'Sentinel32 AUTH: Device authenticated successfully.'
    );
}


/*
 * ============================================================
 * CLEAN TEXT
 * ============================================================
 */

function cleanText(
    mixed $value,
    int $max = 255
): string
{
    $s =
        trim(
            (string)$value
        );

    return mb_substr(
        $s,
        0,
        $max
    );
}


/*
 * ============================================================
 * TELEGRAM NOTIFICATION
 * ============================================================
 */

function sendTelegram(
    string $message
): bool
{
    $token =
        telegramBotToken();

    $chatId =
        telegramChatId();


    /*
     * Telegram disabled or configuration missing.
     */

    if (
        !telegramEnabled() ||
        $token === '' ||
        $chatId === ''
    ) {

        return false;
    }


    /*
     * Telegram Bot API endpoint
     */

    $url =
        'https://api.telegram.org/bot' .
        $token .
        '/sendMessage';


    /*
     * Telegram payload
     */

    $payload = [

        'chat_id' =>
            $chatId,

        'text' =>
            $message,

        'parse_mode' =>
            'HTML',

        'disable_web_page_preview' =>
            true
    ];


    /*
     * CURL request
     */

    $ch =
        curl_init($url);


    curl_setopt_array(
        $ch,
        [

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                http_build_query(
                    $payload
                ),

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_CONNECTTIMEOUT =>
                4,

            CURLOPT_TIMEOUT =>
                8
        ]
    );


    /*
     * Execute Telegram request
     */

    $result =
        curl_exec($ch);


    /*
     * Get HTTP status
     */

    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    /*
     * Determine result
     */

    $ok =
        $result !== false &&
        $httpCode >= 200 &&
        $httpCode < 300;


    /*
     * Log Telegram errors
     */

    if (!$ok) {

        error_log(
            'Sentinel32 Telegram: notification failed. ' .
            'HTTP=' .
            $httpCode
        );
    }


    curl_close($ch);


    return $ok;
}


/*
 * ============================================================
 * THREAT CSS CLASS
 * ============================================================
 */

function threatClass(
    string $level
): string
{
    return match(
        strtoupper($level)
    ) {

        'CRITICAL' =>
            'critical',

        'HIGH' =>
            'high',

        'MEDIUM' =>
            'medium',

        default =>
            'low'
    };
}
