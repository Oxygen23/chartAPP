<?php
$host = getenv("DB_HOST") ?: "localhost";
$user = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASSWORD") ?: "Omari@1987#";
$database = getenv("DB_NAME") ?: "chat_app";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function checkSessionTimeout() {
    $timeout_seconds = 180;

    if (isset($_SESSION["user_id"])) {
        if (isset($_SESSION["last_activity"]) && time() - $_SESSION["last_activity"] > $timeout_seconds) {
            session_unset();
            session_destroy();
            header("Location: login.php?timeout=1");
            exit();
        }

        $_SESSION["last_activity"] = time();
    }
}
?>
