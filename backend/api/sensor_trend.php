<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

require_once __DIR__ . "/../config/db.php";

try {
    $stmt = $pdo->query("
        SELECT temperature, humidity, moisture, recorded_at
        FROM sensor_readings
        ORDER BY recorded_at DESC
        LIMIT 20
    ");
    $rows = $stmt->fetchAll();
    $rows = array_reverse($rows); // oldest to newest for chart
    echo json_encode($rows);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
}
?>
