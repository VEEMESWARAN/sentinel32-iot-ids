<?php

require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $ip_address = mysqli_real_escape_string($conn, $_POST['ip_address']);
    $attack_type = mysqli_real_escape_string($conn, $_POST['attack_type']);
    $threat_level = mysqli_real_escape_string($conn, $_POST['threat_level']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $sql = "INSERT INTO alerts
            (ip_address, attack_type, threat_level, description)
            VALUES
            ('$ip_address', '$attack_type', '$threat_level', '$description')";

    if(mysqli_query($conn, $sql)) {
        echo "SUCCESS";
    } else {
        echo "ERROR";
    }

} else {
    echo "INVALID REQUEST";
}

?>