<?php
require_once __DIR__ . "/../config/db.php";

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forgot_password_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo json_encode(["success" => true, "message" => "forgot_password_logs table created successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to create table: " . $e->getMessage()]);
}
