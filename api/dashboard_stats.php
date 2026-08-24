<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/functions.php';
try {
    $pdo=db();
    $pdo->exec("UPDATE sensors SET status='OFFLINE' WHERE last_seen < (CURRENT_TIMESTAMP - INTERVAL '90 seconds')");
    $latest=$pdo->query("SELECT * FROM telemetry ORDER BY id DESC LIMIT 1")->fetch() ?: [];
    $stats=[
      'online_sensors'=>(int)$pdo->query("SELECT COUNT(*) FROM sensors WHERE status='ONLINE'")->fetchColumn(),
      'alerts_today'=>(int)$pdo->query("SELECT COUNT(*) FROM intrusion_alerts WHERE created_at::date = CURRENT_DATE")->fetchColumn(),
      'critical_today'=>(int)$pdo->query("SELECT COUNT(*) FROM intrusion_alerts WHERE created_at::date = CURRENT_DATE AND threat_level IN ('HIGH','CRITICAL')")->fetchColumn(),
      'latest'=>$latest
    ];
    $alerts=$pdo->query("SELECT id,device_id,source_ip,source_mac,attack_type,threat_level,packets_per_second,description,status,to_char(created_at AT TIME ZONE 'Asia/Kuala_Lumpur','YYYY-MM-DD HH24:MI:SS') created_at FROM intrusion_alerts ORDER BY id DESC LIMIT 12")->fetchAll();
    $chart=$pdo->query("SELECT packets_per_second pps, to_char(created_at AT TIME ZONE 'Asia/Kuala_Lumpur','HH24:MI:SS') label FROM telemetry ORDER BY id DESC LIMIT 30")->fetchAll();
    $chart=array_reverse($chart);
    jsonResponse(['ok'=>true,'stats'=>$stats,'alerts'=>$alerts,'chart'=>$chart]);
} catch(Throwable $e) {
    error_log('Dashboard stats error: '.$e->getMessage());
    jsonResponse(['ok'=>false,'message'=>'Dashboard statistics unavailable'],500);
}
