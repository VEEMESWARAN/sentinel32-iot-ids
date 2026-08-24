<?php
declare(strict_types=1);

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Kuala_Lumpur');

function envValue(string $key, string $default = ''): string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function deviceApiKey(): string { return envValue('DEVICE_API_KEY'); }
function telegramBotToken(): string { return envValue('TELEGRAM_BOT_TOKEN'); }
function telegramChatId(): string { return envValue('TELEGRAM_CHAT_ID'); }
function telegramEnabled(): bool {
    return filter_var(envValue('TELEGRAM_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $databaseUrl = envValue('DATABASE_URL');
    if ($databaseUrl === '') {
        throw new RuntimeException('DATABASE_URL environment variable is not configured.');
    }

    $parts = parse_url($databaseUrl);
    if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
        throw new RuntimeException('DATABASE_URL is invalid.');
    }

    $host = $parts['host'];
    $port = $parts['port'] ?? 5432;
    $dbname = ltrim($parts['path'], '/');
    $user = isset($parts['user']) ? urldecode($parts['user']) : '';
    $pass = isset($parts['pass']) ? urldecode($parts['pass']) : '';

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
