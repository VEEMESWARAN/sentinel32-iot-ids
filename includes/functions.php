<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| READ JSON REQUEST
|--------------------------------------------------------------------------
*/

function requestJson(): array
{
    $raw = file_get_contents('php://input') ?: '';

    $data = json_decode($raw, true);

    if (!is_array($data)) {

        jsonResponse([
            'ok' => false,
            'error' => 'Invalid JSON body'
        ], 400);
    }

    return $data;
}


/*
|--------------------------------------------------------------------------
| DEVICE AUTHENTICATION
|--------------------------------------------------------------------------
|
| Sentinel32 supports:
|
| 1. X-API-Key header
| 2. Apache environment header
| 3. apache_request_headers()
| 4. getallheaders()
| 5. api_key inside JSON payload
|
| This is required because some hosted Apache configurations may not
| expose X-API-Key through $_SERVER['HTTP_X_API_KEY'].
|
*/

function requireDeviceKey(?array $data = null): void
{
    /*
     * API key configured in Render:
     *
     * DEVICE_API_KEY
     */

    $expected = trim(
        (string) deviceApiKey()
    );


    /*
     * Make sure Render has DEVICE_API_KEY configured.
     */

    if ($expected === '') {

        error_log(
            'Sentinel32 AUTH ERROR: DEVICE_API_KEY is empty on server.'
        );

        jsonResponse([
            'ok' => false,
            'error' => 'DEVICE_API_KEY not configured'
        ], 500);
    }


    /*
     * Received key.
     */

    $key = '';

    /*
     * Used only for debugging.
     * We never expose the actual key.
     */

    $authMethod = 'NONE';


    /*
    |--------------------------------------------------------------------------
    | METHOD 1
    | $_SERVER HTTP_X_API_KEY
    |--------------------------------------------------------------------------
    */

    if (
        isset($_SERVER['HTTP_X_API_KEY']) &&
        trim((string)$_SERVER['HTTP_X_API_KEY']) !== ''
    ) {

        $key = trim(
            (string)$_SERVER['HTTP_X_API_KEY']
        );

        $authMethod = 'HTTP_HEADER';
    }


    /*
    |--------------------------------------------------------------------------
    | METHOD 2
    | Apache environment
    |--------------------------------------------------------------------------
    */

    if ($key === '') {

        $envKey = getenv(
            'HTTP_X_API_KEY'
        );

        if (
            $envKey !== false &&
            trim((string)$envKey) !== ''
        ) {

            $key = trim(
                (string)$envKey
            );

            $authMethod = 'APACHE_ENV';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | METHOD 3
    | apache_request_headers()
    |--------------------------------------------------------------------------
    */

    if (
        $key === '' &&
        function_exists('apache_request_headers')
    ) {

        $headers = apache_request_headers();

        if (is_array($headers)) {

            foreach ($headers as $name => $value) {

                if (
                    strcasecmp(
                        (string)$name,
                        'X-API-Key'
                    ) === 0
                ) {

                    $key = trim(
                        (string)$value
                    );

                    $authMethod =
                        'APACHE_REQUEST_HEADERS';

                    break;
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | METHOD 4
    | getallheaders()
    |--------------------------------------------------------------------------
    */

    if (
        $key === '' &&
        function_exists('getallheaders')
    ) {

        $headers = getallheaders();

        if (is_array($headers)) {

            foreach ($headers as $name => $value) {

                if (
                    strcasecmp(
                        (string)$name,
                        'X-API-Key'
                    ) === 0
                ) {

                    $key = trim(
                        (string)$value
                    );

                    $authMethod =
                        'GETALLHEADERS';

                    break;
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | METHOD 5
    | JSON BODY FALLBACK
    |--------------------------------------------------------------------------
    |
    | Latest ESP32 firmware sends:
    |
    | {
    |     "api_key": "...",
    |     "device_id": "ESP32-IDS-01",
    |     ...
    | }
    |
    */

    if (
        $key === '' &&
        is_array($data) &&
        isset($data['api_key'])
    ) {

        $jsonKey = trim(
            (string)$data['api_key']
        );

        if ($jsonKey !== '') {

            $key = $jsonKey;

            $authMethod =
                'JSON_BODY';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | NO KEY RECEIVED
    |--------------------------------------------------------------------------
    */

    if ($key === '') {

        error_log(
            'Sentinel32 AUTH: No API key received.'
        );

        jsonResponse([
            'ok' => false,
            'error' => 'Device API key missing'
        ], 401);
    }


    /*
    |--------------------------------------------------------------------------
    | COMPARE KEY
    |--------------------------------------------------------------------------
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
            'Method=' . $authMethod .
            ' ReceivedLength=' . strlen($key) .
            ' ExpectedLength=' . strlen($expected)
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
    |--------------------------------------------------------------------------
    | AUTHENTICATION SUCCESS
    |--------------------------------------------------------------------------
    */

    error_log(
        'Sentinel32 AUTH: Device authenticated using ' .
        $authMethod
    );
}


/*
|--------------------------------------------------------------------------
| CLEAN TEXT
|--------------------------------------------------------------------------
*/

function cleanText(
    mixed $value,
    int $max = 255
): string
{
    $s = trim(
        (string)$value
    );

    return mb_substr(
        $s,
        0,
        $max
    );
}


/*
|--------------------------------------------------------------------------
| TELEGRAM
|--------------------------------------------------------------------------
*/

function sendTelegram(string $message): bool
{
    $token = telegramBotToken();

    $chatId = telegramChatId();


    if (
        !telegramEnabled() ||
        $token === '' ||
        $chatId === ''
    ) {

        return false;
    }


    $url =
        'https://api.telegram.org/bot' .
        $token .
        '/sendMessage';


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


    $ch = curl_init($url);


    if ($ch === false) {

        error_log(
            'Sentinel32 Telegram: CURL initialization failed.'
        );

        return false;
    }


    curl_setopt_array(
        $ch,
        [

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                http_build_query($payload),

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_CONNECTTIMEOUT =>
                4,

            CURLOPT_TIMEOUT =>
                8
        ]
    );


    $result = curl_exec($ch);


    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


    $ok =
        $result !== false &&
        $httpCode >= 200 &&
        $httpCode < 300;


    if (!$ok) {

        error_log(
            'Sentinel32 Telegram error. HTTP=' .
            $httpCode .
            ' CURL=' .
            curl_error($ch)
        );
    }


    curl_close($ch);


    return $ok;
}


/*
|--------------------------------------------------------------------------
| THREAT CLASS
|--------------------------------------------------------------------------
*/

function threatClass(string $level): string
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
