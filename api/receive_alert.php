<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/functions.php';
requireDeviceKey();
$d = requestJson();

$deviceId = cleanText($d['device_id'] ?? '',50);
$attack = cleanText($d['attack_type'] ?? '',100);
$level = strtoupper(cleanText($d['threat_level'] ?? 'MEDIUM',20));
$allowed = ['LOW','MEDIUM','HIGH','CRITICAL'];
if (!in_array($level,$allowed,true)) $level='MEDIUM';
if ($deviceId==='' || $attack==='') jsonResponse(['ok'=>false,'error'=>'device_id and attack_type required'],422);

$stmt = db()->prepare(
    "INSERT INTO intrusion_alerts
    (device_id,source_ip,source_mac,destination_ip,source_port,destination_port,protocol,
     attack_type,threat_level,packet_count,packets_per_second,description)
     VALUES(?,?,?,?,?,?,?,?,?,?,?,?)"
);
$stmt->execute([
    $deviceId,
    cleanText($d['source_ip'] ?? '',45) ?: null,
    cleanText($d['source_mac'] ?? '',17) ?: null,
    cleanText($d['destination_ip'] ?? '',45) ?: null,
    isset($d['source_port']) ? (int)$d['source_port'] : null,
    isset($d['destination_port']) ? (int)$d['destination_port'] : null,
    cleanText($d['protocol'] ?? '',20) ?: null,
    $attack,
    $level,
    (int)($d['packet_count'] ?? 0),
    (float)($d['pps'] ?? 0),
    cleanText($d['description'] ?? 'Suspicious network activity detected.',1000)
]);

$source = cleanText($d['source_ip'] ?? ($d['source_mac'] ?? 'Unknown'),60);
$description = cleanText($d['description'] ?? 'Suspicious network activity detected.',600);
$message =
    "🚨 <b>IoT IDS SECURITY ALERT</b>\n\n".
    "Device: <b>".htmlspecialchars($deviceId)."</b>\n".
    "Threat: <b>".htmlspecialchars($level)."</b>\n".
    "Attack: <b>".htmlspecialchars($attack)."</b>\n".
    "Source: <code>".htmlspecialchars($source)."</code>\n".
    "PPS: <b>".number_format((float)($d['pps'] ?? 0),2)."</b>\n".
    "Details: ".htmlspecialchars($description)."\n".
    "Time: ".date('Y-m-d H:i:s');

$telegram = sendTelegram($message);
jsonResponse(['ok'=>true,'message'=>'Alert stored','telegram_sent'=>$telegram]);
