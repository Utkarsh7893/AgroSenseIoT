<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

require_once __DIR__ . "/../config/db.php";
$userId = (int)$_SESSION["user_id"];

try {
    // unread alerts count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) AS unread_count
        FROM alerts a
        INNER JOIN plots p ON a.plot_id = p.id
        INNER JOIN farms f ON p.farm_id = f.id
        WHERE f.user_id = :user_id AND a.is_read = 0
    ");
    $countStmt->execute([":user_id" => $userId]);
    $countRow = $countStmt->fetch();

    // latest 5 unread alerts
    $listStmt = $pdo->prepare("
        SELECT a.severity, a.message, a.created_at, p.plot_name, f.farm_name
        FROM alerts a
        INNER JOIN plots p ON a.plot_id = p.id
        INNER JOIN farms f ON p.farm_id = f.id
        WHERE f.user_id = :user_id AND a.is_read = 0
        ORDER BY a.id DESC
        LIMIT 5
    ");
    $listStmt->execute([":user_id" => $userId]);
    $alerts = $listStmt->fetchAll();

    echo json_encode([
        "unread_count" => (int)($countRow["unread_count"] ?? 0),
        "alerts" => $alerts
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
}
?>
