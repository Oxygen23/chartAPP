<?php
session_start();
include "connection.php";

if (isset($_GET["logout"])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if (isset($_SESSION["user_id"])) {
    checkSessionTimeout();
    header("Location: dashboard.php");
    exit();
}

$error = "";
$message = "";

if (isset($_GET["timeout"])) {
    $message = "Your session has expired because you were inactive for 3 minutes. Please log in again.";
}

if (isset($_POST["login"])) {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($email == "" || $password == "") {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "The password is incorrect.";
            }
        } else {
            $error = "The email address was not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chat App</title>
    <link rel="stylesheet" href="asset/chart.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-box">
            <h1>Login</h1>
            <p class="small-text">Log in first, then choose a chat room.</p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>

            <?php if ($message != "") { ?>
                <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
            <?php } ?>

            <form method="POST" class="form" id="loginForm">
                <label>Email</label>
                <input type="email" name="email" id="loginEmail" placeholder="Enter your email" required>
                <small class="field-error" id="loginEmailError"></small>

                <label>Password</label>
                <input type="password" name="password" id="loginPassword" placeholder="Enter your password" required>
                <small class="field-error" id="loginPasswordError"></small>

                <button type="submit" name="login">Login</button>
            </form>

            <p class="link-text">Do not have an account? <a href="registration.php">Register here</a></p>
        </section>
    </main>
    <script src="asset/chart.js"></script>
</body>
</html>
