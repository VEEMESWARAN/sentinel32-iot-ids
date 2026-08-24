<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/functions.php';
$sent=sendTelegram("✅ <b>IoT IDS Telegram Test</b>\n\nYour notification integration is working.\nTime: ".date('Y-m-d H:i:s'));
jsonResponse(['ok'=>$sent,'message'=>$sent?'Telegram test sent':'Telegram disabled or configuration failed']);
