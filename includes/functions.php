<?php
declare(strict_types=1);

/*
 * ============================================================
 * SENTINEL32 IoT IDS
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

    header(
        'Cache-Control: no-store'
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
    $raw = file_get_contents('php://input') ?: '';

    $data = json_decode(
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
 * DEVICE API KEY AUTHENTICATION
 * ============================================================
 *
 * Authentication methods:
 *
 * 1. X-API-Key HTTP header
 * 2. Apache HTTP_X_API_KEY
 * 3. Apache request headers
 * 4. PHP getallheaders()
 * 5. JSON body api_key
 *
 * JSON body is used as fallback because some hosted
 * Apache configurations may not expose custom headers
 * correctly to PHP.
 *
 * ============================================================
 */

function requireDeviceKey(?array $data = null): void
{
    /*
     * --------------------------------------------------------
     * EXPECTED API KEY
     * --------------------------------------------------------
     */

    $expected = trim(
        (string) deviceApiKey()
    );


    /*
     * Check server configuration.
     */

    if ($expected === '') {

        error_log(
            'Sentinel32 AUTH: DEVICE_API_KEY is missing.'
        );

        jsonResponse([
            'ok' => false,
            'error' => 'DEVICE_API_KEY missing on server'
        ], 500);
    }


    /*
     * Variable that will contain the key received
     * from the ESP32.
     */

    $key = '';

    $authMethod = '';


    /*
     * ========================================================
     * METHOD 1
     * Standard PHP / Apache mapping
     * ========================================================
     *
     * X-API-Key normally becomes:
     *
     * HTTP_X_API_KEY
     */

    if (
        isset($_SERVER['HTTP_X_API_KEY']) &&
        trim((string) $_SERVER['HTTP_X_API_KEY']) !== ''
    ) {

        $key = trim(
            (string) $_SERVER['HTTP_X_API_KEY']
        );

        $authMethod = 'HTTP_X_API_KEY';
    }


    /*
     * ========================================================
     * METHOD 2
     * Apache environment variable
     * ========================================================
     */

    if ($key === '') {

        $environmentKey = getenv(
            'HTTP_X_API_KEY'
        );

        if (
            $environmentKey !== false &&
            trim((string) $environmentKey) !== ''
        ) {

            $key = trim(
                (string) $environmentKey
            );

            $authMethod = 'APACHE_ENV';
        }
    }


    /*
     * ========================================================
     * METHOD 3
     * apache_request_headers()
     * ========================================================
     */

    if (
        $key === '' &&
        function_exists('apache_request_headers')
    ) {

        $headers = apache_request_headers();

        if (is_array($headers)) {

            foreach (
                $headers as
                $headerName => $headerValue
            ) {

                if (
                    strcasecmp(
                        (string) $headerName,
                        'X-API-Key'
                    ) === 0
                ) {

                    $key = trim(
                        (string) $headerValue
                    );

                    $authMethod =
                        'APACHE_REQUEST_HEADERS';

                    break;
                }
            }
        }
    }


    /*
     * ========================================================
     * METHOD 4
     * getallheaders()
     * ========================================================
     */

    if (
        $key === '' &&
        function_exists('getallheaders')
    ) {

        $headers = getallheaders();

        if (is_array($headers)) {

            foreach (
                $headers as
                $headerName => $headerValue
            ) {

                if (
                    strcasecmp(
                        (string) $headerName,
                        'X-API-Key'
                    ) === 0
                ) {

                    $key = trim(
                        (string) $headerValue
                    );

                    $authMethod =
                        'GETALLHEADERS';

                    break;
                }
            }
        }
    }


    /*
     * ========================================================
     * METHOD 5
     * JSON BODY FALLBACK
     * ========================================================
     *
     * Expected:
     *
     * {
     *   "api_key": "...",
     *   "device_id": "ESP32-IDS-01"
     * }
     */

    if (
        $key === '' &&
        is_array($data) &&
        isset($data['api_key']) &&
        trim((string) $data['api_key']) !== ''
    ) {

        $key = trim(
            (string) $data['api_key']
        );

        $authMethod =
            'JSON_BODY';
    }


    /*
     * ========================================================
     * NO API KEY RECEIVED
     * ========================================================
     */

    if ($key === '') {

        error_log(
            'Sentinel32 AUTH: No device API key received.'
        );

        jsonResponse([
            'ok' => false,
            'error' => 'Device API key missing'
        ], 401);
    }


    /*
     * ========================================================
     * COMPARE API KEYS
     * ========================================================
     */

    if (
        !hash_equals(
            $expected,
            $key
        )
    ) {

        /*
         * Never log the actual API key.
         */

        error_log(
            'Sentinel32 AUTH: API key mismatch. ' .
            'Method=' .
            $authMethod .
            ', ReceivedLength=' .
            strlen($key) .
            ', ExpectedLength=' .
            strlen($expected)
        );


        jsonResponse([
            'ok' => false,
            'error' => 'API key mismatch',
            'auth_method' => $authMethod,
            'received_length' => strlen($key),
            'expected_length' => strlen($expected)
        ], 401);
    }


    /*
     * ========================================================
     * AUTHENTICATION SUCCESS
     * ========================================================
     */

    error_log(
        'Sentinel32 AUTH: Device authenticated. Method=' .
        $authMethod
    );

    /*
     * Do not return a JSON response here.
     *
     * Execution continues inside:
     *
     * heartbeat.php
     * receive_telemetry.php
     * receive_alert.php
     */
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
    $text = trim(
        (string) $value
    );

    return mb_substr(
        $text,
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
    /*
     * Get Telegram configuration from config.php
     */

    $token = telegramBotToken();

    $chatId = telegramChatId();


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
     * Telegram Bot API URL
     */

    $url =
        'https://api.telegram.org/bot' .
        $token .
        '/sendMessage';


    /*
     * Telegram request payload
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
     * Initialize CURL
     */

    $ch = curl_init(
        $url
    );


    if ($ch === false) {

        error_log(
            'Sentinel32 Telegram: CURL initialization failed.'
        );

        return false;
    }


    /*
     * CURL configuration
     */

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

    $result = curl_exec(
        $ch
    );


    /*
     * HTTP status
     */

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


    /*
     * Determine success
     */

    $success =
        $result !== false &&
        $httpCode >= 200 &&
        $httpCode < 300;


    /*
     * Log failure
     */

    if (!$success) {

        $curlError = curl_error(
            $ch
        );

        error_log(
            'Sentinel32 Telegram: Notification failed. ' .
            'HTTP=' .
            $httpCode .
            ', CURL=' .
            $curlError
        );
    }


    /*
     * Close CURL
     */

    curl_close(
        $ch
    );


    return $success;
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
    return match (
        strtoupper(
            trim($level)
        )
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
