<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/functions.php';
$d=requestJson();
$id=(int)($d['id'] ?? 0);
$status=strtoupper(cleanText($d['status'] ?? 'ACKNOWLEDGED',20));
if (!in_array($status,['NEW','ACKNOWLEDGED','RESOLVED'],true)) jsonResponse(['ok'=>false,'error'=>'Invalid status'],422);
$stmt=db()->prepare("UPDATE intrusion_alerts SET status=? WHERE id=?");
$stmt->execute([$status,$id]);
jsonResponse(['ok'=>true]);
