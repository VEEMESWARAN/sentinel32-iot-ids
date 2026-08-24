<?php

require_once "db.php";

header('Content-Type: application/json');

$response = [];

/* Total Alerts */
$total = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) as total FROM alerts"
    )
);

/* Critical Alerts */
$critical = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) as critical_count
         FROM alerts
         WHERE threat_level='CRITICAL'"
    )
);

/* High Alerts */
$high = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) as high_count
         FROM alerts
         WHERE threat_level='HIGH'"
    )
);

/* Latest Alerts */
$alertsQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM alerts
     ORDER BY created_at DESC
     LIMIT 50"
);

$alerts = [];

while($row = mysqli_fetch_assoc($alertsQuery)) {
    $alerts[] = $row;
}

$response['total'] = $total['total'];
$response['critical'] = $critical['critical_count'];
$response['high'] = $high['high_count'];
$response['alerts'] = $alerts;

echo json_encode($response);

?>