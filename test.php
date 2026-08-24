<?php

$url = "http://localhost/IoT_IDS/receive_alert.php";

$data = [
    "ip_address" => "172.20.10.10",
    "attack_type" => "Traffic Flood",
    "threat_level" => "HIGH",
    "description" => "Test Alert"
];

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);

$result = file_get_contents($url, false, $context);

echo $result;
?>