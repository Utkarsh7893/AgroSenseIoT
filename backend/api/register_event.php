<?php
/**
 * API: Register interest in an event.
 * Method: POST
 * Params: event_slug, event_name, full_name, email, phone (optional), message (optional)
 * Returns JSON success/error response.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Only POST method allowed."]);
    exit;
}

require_once __DIR__ . "/../config/db.php";

// Read input (supports both form and JSON)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

$eventSlug = trim($input["event_slug"] ?? "");
$eventName = trim($input["event_name"] ?? "");
$fullName  = trim($input["full_name"] ?? "");
$email     = trim($input["email"] ?? "");
$phone     = trim($input["phone"] ?? "");
$message   = trim($input["message"] ?? "");

// Validation
$errors = [];
if ($eventSlug === "") $errors[] = "Event identifier is required.";
if ($eventName === "") $errors[] = "Event name is required.";
if ($fullName === "")  $errors[] = "Full name is required.";
if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(["error" => implode(" ", $errors)]);
    exit;
}

try {
    // Check if already registered
    $check = $pdo->prepare("
        SELECT id FROM event_registrations 
        WHERE event_slug = :event_slug AND email = :email 
        LIMIT 1
    ");
    $check->execute([":event_slug" => $eventSlug, ":email" => $email]);

    if ($check->fetch()) {
        echo json_encode([
            "success" => true,
            "message" => "You've already registered for this event! We'll keep you updated."
        ]);
        exit;
    }

    // Insert registration
    $stmt = $pdo->prepare("
        INSERT INTO event_registrations (event_slug, event_name, full_name, email, phone, message)
        VALUES (:event_slug, :event_name, :full_name, :email, :phone, :message)
    ");
    $stmt->execute([
        ":event_slug"  => $eventSlug,
        ":event_name"  => $eventName,
        ":full_name"   => $fullName,
        ":email"       => $email,
        ":phone"       => $phone !== "" ? $phone : null,
        ":message"     => $message !== "" ? $message : null,
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Successfully registered! We'll contact you with event details soon."
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Registration failed. Please try again later."]);
}
