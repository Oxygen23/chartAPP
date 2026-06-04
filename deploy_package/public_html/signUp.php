<?php
session_start();
include "connection.php";

$error = "";
$success = "";

if (isset($_POST["signup"])) {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($username == "" || $email == "" || $password == "" || $confirm_password == "") {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password != $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);

        if ($check->rowCount() > 0) {
            $error = "The username or email is already in use.";
        } else {
            $safe_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

            if ($insert->execute([$username, $email, $safe_password])) {
                $success = "Account created successfully. You can now log in.";
            } else {
                $error = "Account was not created. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Chat App</title>
    <link rel="stylesheet" href="asset/chart.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-box">
            <h1>Create Account</h1>
            <p class="small-text">Join so you can enter the chat room.</p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>

            <?php if ($success != "") { ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php } ?>

            <form method="POST" class="form" id="signupForm">
                <label>Username</label>
                <input type="text" name="username" id="signupUsername" placeholder="Example: amina" required>
                <small class="field-error" id="signupUsernameError"></small>

                <label>Email</label>
                <input type="email" name="email" id="signupEmail" placeholder="Example: amina@email.com" required>
                <small class="field-error" id="signupEmailError"></small>

                <label>Password</label>
                <input type="password" name="password" id="signupPassword" placeholder="At least 6 characters" required>
                <small class="field-error" id="signupPasswordError"></small>

                <label>Confirm Password</label>
                <input type="password" name="confirm_password" id="confirmPassword" placeholder="Repeat password" required>
                <small class="field-error" id="confirmPasswordError"></small>

                <button type="submit" name="signup">Sign Up</button>
            </form>

            <p class="link-text">Already have an account? <a href="login.php">Log in here</a></p>
        </section>
    </main>
    <script src="asset/chart.js"></script>
</body>
</html>
