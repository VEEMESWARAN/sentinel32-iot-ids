<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/functions.php';
try {
    db()->query('SELECT 1');
    jsonResponse(['ok'=>true,'app'=>'Sentinel32 IoT IDS','database'=>'connected','time'=>date(DATE_ATOM)]);
} catch(Throwable $e) {
    jsonResponse(['ok'=>false,'app'=>'Sentinel32 IoT IDS','database'=>'unavailable','time'=>date(DATE_ATOM)],503);
}
