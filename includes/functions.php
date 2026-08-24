<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function jsonResponse(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function requestJson(): array {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        jsonResponse(['ok'=>false,'error'=>'Invalid JSON body'], 400);
    }
    return $data;
}

function requireDeviceKey(): void {
    $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
    $expected = deviceApiKey();
    if ($expected === '' || !hash_equals($expected, $key)) {
        jsonResponse(['ok'=>false,'error'=>'Unauthorized device'], 401);
    }
}

function cleanText(mixed $value, int $max = 255): string {
    $s = trim((string)$value);
    return mb_substr($s, 0, $max);
}

function sendTelegram(string $message): bool {
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
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8,
    ]);
    $result = curl_exec($ch);
    $ok = $result !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 200 && curl_getinfo($ch, CURLINFO_HTTP_CODE) < 300;
    curl_close($ch);
    return $ok;
}

function threatClass(string $level): string {
    return match(strtoupper($level)) {
        'CRITICAL' => 'critical',
        'HIGH' => 'high',
        'MEDIUM' => 'medium',
        default => 'low'
    };
}
