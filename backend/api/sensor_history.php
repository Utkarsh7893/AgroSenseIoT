<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

require_once __DIR__ . "/../config/db.php";

$start = $_GET["start"] ?? null;
$end = $_GET["end"] ?? null;

if (!$start || !$end) {
    http_response_code(400);
    echo json_encode(["error" => "start and end dates are required"]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT temperature, humidity, moisture, recorded_at
        FROM sensor_readings
        WHERE DATE(recorded_at) BETWEEN :start AND :end
        ORDER BY recorded_at ASC
    ");
    $stmt->execute([
        ":start" => $start,
        ":end" => $end
    ]);

    $rows = $stmt->fetchAll();
    echo json_encode($rows);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
}
?>
