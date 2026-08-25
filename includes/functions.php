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
| DEVICE API AUTHENTICATION
|--------------------------------------------------------------------------
|
| Reads the X-API-Key header sent by the ESP32.
|
| Existing deviceApiKey() from config.php is used.
|
*/

function requireDeviceKey(): void
{
    /*
     * Expected key from existing config.php
     */

    $expected = trim(
        (string) deviceApiKey()
    );


    /*
     * Make sure server has a configured API key.
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


    $key = '';


    /*
     * METHOD 1
     * Standard Apache/PHP header mapping.
     */

    if (
        isset($_SERVER['HTTP_X_API_KEY']) &&
        $_SERVER['HTTP_X_API_KEY'] !== ''
    ) {

        $key = trim(
            (string) $_SERVER['HTTP_X_API_KEY']
        );
    }


    /*
     * METHOD 2
     * Apache environment variable fallback.
     */

    if ($key === '') {

        $envKey = getenv(
            'HTTP_X_API_KEY'
        );

        if (
            $envKey !== false &&
            $envKey !== ''
        ) {

            $key = trim(
                (string) $envKey
            );
        }
    }


    /*
     * METHOD 3
     * Apache request headers.
     */

    if (
        $key === '' &&
        function_exists('apache_request_headers')
    ) {

        $headers = apache_request_headers();

        if (is_array($headers)) {

            foreach (
                $headers as
                $name => $value
            ) {

                if (
                    strcasecmp(
                        (string) $name,
                        'X-API-Key'
                    ) === 0
                ) {

                    $key = trim(
                        (string) $value
                    );

                    break;
                }
            }
        }
    }


    /*
     * METHOD 4
     * Generic PHP getallheaders fallback.
     */

    if (
        $key === '' &&
        function_exists('getallheaders')
    ) {

        $headers = getallheaders();

        if (is_array($headers)) {

            foreach (
                $headers as
                $name => $value
            ) {

                if (
                    strcasecmp(
                        (string) $name,
                        'X-API-Key'
                    ) === 0
                ) {

                    $key = trim(
                        (string) $value
                    );

                    break;
                }
            }
        }
    }


    /*
     * HEADER NOT RECEIVED
     */

    if ($key === '') {

        error_log(
            'Sentinel32 AUTH: X-API-Key header was NOT received.'
        );

        jsonResponse([
            'ok' => false,
            'error' => 'X-API-Key header missing'
        ], 401);
    }


    /*
     * KEY DOES NOT MATCH
     *
     * Only lengths are returned.
     * Actual API keys are never exposed.
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
            ', expected length=' .
            strlen($expected)
        );

        jsonResponse([
            'ok' => false,
            'error' => 'API key mismatch',
            'received_length' => strlen($key),
            'expected_length' => strlen($expected)
        ], 401);
    }


    /*
     * SUCCESS
     *
     * No response is generated here.
     * heartbeat.php / receive_telemetry.php continues normally.
     */

    error_log(
        'Sentinel32 AUTH: Device authenticated successfully.'
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
        (string) $value
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

function sendTelegram(
    string $message
): bool
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
            $httpCode
        );
    }


    curl_close($ch);


    return $ok;
}


/*
|--------------------------------------------------------------------------
| THREAT CSS CLASS
|--------------------------------------------------------------------------
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
