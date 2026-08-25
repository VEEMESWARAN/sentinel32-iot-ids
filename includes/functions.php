<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

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

function requireDeviceKey(?array $data = null): void
{
    $expected = trim((string) deviceApiKey());

    if ($expected === '') {
        error_log('Sentinel32 AUTH: DEVICE_API_KEY is not configured.');

        jsonResponse([
            'ok' => false,
            'error' => 'DEVICE_API_KEY not configured'
        ], 500);
    }

    $key = '';
    $method = 'NONE';

    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $key = trim((string) $_SERVER['HTTP_X_API_KEY']);
        $method = 'HTTP_HEADER';
    }

    if ($key === '' && function_exists('getallheaders')) {
        $headers = getallheaders();

        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (strcasecmp((string) $name, 'X-API-Key') === 0) {
                    $key = trim((string) $value);
                    $method = 'GETALLHEADERS';
                    break;
                }
            }
        }
    }

    if (
        $key === '' &&
        is_array($data) &&
        isset($data['api_key'])
    ) {
        $key = trim((string) $data['api_key']);
        $method = 'JSON_BODY';
    }

    if ($key === '') {
        error_log('Sentinel32 AUTH: Device API key missing.');

        jsonResponse([
            'ok' => false,
            'error' => 'Device API key missing'
        ], 401);
    }

    if (!hash_equals($expected, $key)) {
        error_log(
            'Sentinel32 AUTH: API key mismatch. Method=' .
            $method .
            ' ReceivedLength=' .
            strlen($key) .
            ' ExpectedLength=' .
            strlen($expected)
        );

        jsonResponse([
            'ok' => false,
            'error' => 'API key mismatch'
        ], 401);
    }
}

function cleanText(mixed $value, int $max = 255): string
{
    $s = trim((string) $value);
    return mb_substr($s, 0, $max);
}

function sendTelegram(string $message): bool
{
    $token = telegramBotToken();
    $chatId = telegramChatId();

    if (!telegramEnabled() || $token === '' || $chatId === '') {
        return false;
    }

    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';

    $payload = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    $ch = curl_init($url);

    if ($ch === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8,
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $ok =
        $result !== false &&
        $httpCode >= 200 &&
        $httpCode < 300;

    curl_close($ch);

    return $ok;
}

function threatClass(string $level): string
{
    return match (strtoupper($level)) {
        'CRITICAL' => 'critical',
        'HIGH' => 'high',
        'MEDIUM' => 'medium',
        default => 'low'
    };
}
