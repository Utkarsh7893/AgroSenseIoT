<?php
/**
 * Run this script once to create the event_registrations table.
 * Access: http://localhost/AgroSenseIoT/backend/api/create_event_table.php
 */
require_once __DIR__ . "/../config/db.php";

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS event_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_slug VARCHAR(100) NOT NULL,
            event_name VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            message TEXT DEFAULT NULL,
            registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_slug (event_slug),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo json_encode(["success" => true, "message" => "event_registrations table created successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to create table: " . $e->getMessage()]);
}
