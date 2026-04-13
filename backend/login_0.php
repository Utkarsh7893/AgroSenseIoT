<?php
session_start();
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Invalid request method.";
    exit();
}

$username = trim($_POST["Username"] ?? "");
$password = trim($_POST["password"] ?? "");

if ($username === "" || $password === "") {
    echo "<h3>Username and password are required.</h3>";
    echo "<p><a href='../index.html'>Try again</a></p>";
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, name, username, password_hash FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([":username" => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["name"] = $user["name"];

        header("Location: ../mycomponents/dashboard.php");
        exit();
    }

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Invalid Login</title>
        <style>
            body {
                font-family: sans-serif;
                background-image: url(../img/image8.jpeg);
                background-repeat: no-repeat;
                background-size: cover;
                background-position: center;
                height: 100vh;
                color: white;
                padding: 30px;
            }
            h3 { font-size: 24px; }
            a { color: snow; font-size: 18px; }
        </style>
    </head>
    <body>
        <h3>Invalid username or password!</h3>
        <p><a href='../index.html'><strong>Try again</strong></a></p>
    </body>
    </html>";
} catch (PDOException $e) {
    echo "Login failed. Please try again later.";
}
?>
