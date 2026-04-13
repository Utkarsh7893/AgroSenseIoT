<?php
session_start();
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Invalid request.";
    exit();
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = trim($_POST["password"] ?? "");
$confirmPassword = trim($_POST["confirmPassword"] ?? "");
$username = trim($_POST["Username"] ?? "");

$errors = [];

if ($name === "") $errors[] = "Name is required.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
if ($password !== $confirmPassword) $errors[] = "Passwords do not match.";
if ($username === "") $errors[] = "Username is required.";

if (!empty($errors)) {
    echo "<h2 style='color: red;'>Signup failed:</h2><ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul><p><a href='../mycomponents/sign_up.html'>Go back</a></p>";
    exit();
}

try {
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
    $checkStmt->execute([
        ":username" => $username,
        ":email" => $email
    ]);

    if ($checkStmt->fetch()) {
        echo "<h2 style='color: red;'>Signup failed:</h2>";
        echo "<p>Username or email already exists.</p>";
        echo "<p><a href='../mycomponents/sign_up.html'>Try again</a></p>";
        exit();
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $pdo->prepare("
        INSERT INTO users (name, username, email, password_hash)
        VALUES (:name, :username, :email, :password_hash)
    ");
    $insertStmt->execute([
        ":name" => $name,
        ":username" => $username,
        ":email" => $email,
        ":password_hash" => $passwordHash
    ]);

    $_SESSION["user_id"] = $pdo->lastInsertId();
    $_SESSION["username"] = $username;
    $_SESSION["name"] = $name;

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Signup Success</title>
        <meta http-equiv='refresh' content='3;url=../mycomponents/dashboard.php'>
        <style>
            body { font-family: sans-serif; text-align: center; padding-top: 50px; }
        </style>
    </head>
    <body>
        <h2 style='color: green;'>Signup successful!</h2>
        <p>Welcome, <strong>" . htmlspecialchars($username) . "</strong></p>
        <p>Redirecting to Dashboard...</p>
    </body>
    </html>";
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Signup failed:</h2>";
    echo "<p>Something went wrong. Please try again.</p>";
}
?>
