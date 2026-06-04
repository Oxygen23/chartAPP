<?php
session_start();
include "connection.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

checkSessionTimeout();

if (!isset($_SESSION["role"])) {
    $role_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $role_stmt->execute([$_SESSION["user_id"]]);
    $_SESSION["role"] = $role_stmt->fetchColumn() ?: "user";
}

$rooms = ["general", "students", "business", "sports"];
$room_notifications = [];
$private_notifications = [];

$stmt = $conn->prepare("
    SELECT room_name, COUNT(*) AS total
    FROM notifications
    WHERE user_id = ? AND is_read = 0
    GROUP BY room_name
");
$stmt->execute([$_SESSION["user_id"]]);
$notifications = $stmt->fetchAll();

foreach ($notifications as $notification) {
    if (strpos($notification["room_name"], "private_") === 0) {
        $private_notifications[$notification["room_name"]] = $notification["total"];
    } else {
        $room_notifications[$notification["room_name"]] = $notification["total"];
    }
}

$users = $conn->prepare("
    SELECT id, username
    FROM users
    WHERE id != ?
    ORDER BY username ASC
");
$users->execute([$_SESSION["user_id"]]);
$chat_users = $users->fetchAll();
$private_room_users = [];

function privateRoomName($first_user_id, $second_user_id) {
    $small_id = min($first_user_id, $second_user_id);
    $big_id = max($first_user_id, $second_user_id);

    return "private_" . $small_id . "_" . $big_id;
}

foreach ($chat_users as $chat_user) {
    $private_room = privateRoomName($_SESSION["user_id"], $chat_user["id"]);
    $private_room_users[$private_room] = $chat_user["username"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Chat App</title>
    <link rel="stylesheet" href="asset/chart.css">
</head>
<body data-session-timeout="180">
    <header class="topbar">
        <div>
            <strong>Chat App</strong>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        </div>
        <a class="logout-link" href="login.php?logout=1">Logout</a>
    </header>

    <main class="page">
        <section class="panel">
            <h1>Choose a Room</h1>
            <p class="small-text">Choose a public room or select one user for a private chat.</p>

            <?php if ($_SESSION["role"] === "admin") { ?>
                <div class="admin-action-bar">
                    <a class="admin-link" href="admin.php">Open Admin Panel</a>
                </div>
            <?php } ?>

            <?php if (count($room_notifications) > 0 || count($private_notifications) > 0) { ?>
                <div class="notify-box" data-notify-count="<?php echo array_sum($room_notifications) + array_sum($private_notifications); ?>">
                    You have new messages:
                    <?php foreach ($room_notifications as $room_name => $total) { ?>
                        <a href="chartroom.php?room=<?php echo urlencode($room_name); ?>">
                            <?php echo htmlspecialchars($room_name); ?> (<?php echo $total; ?>)
                        </a>
                    <?php } ?>
                    <?php foreach ($private_notifications as $room_name => $total) { ?>
                        <a href="chartroom.php?room=<?php echo urlencode($room_name); ?>">
                            <?php echo htmlspecialchars($private_room_users[$room_name] ?? "private"); ?> (<?php echo $total; ?>)
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>

            <h2>Public Rooms</h2>
            <div class="room-grid">
                <?php foreach ($rooms as $room) { ?>
                    <a class="room-card" href="chartroom.php?room=<?php echo urlencode($room); ?>">
                        <?php echo htmlspecialchars(ucfirst($room)); ?>
                        <?php if (isset($room_notifications[$room])) { ?>
                            <span class="room-badge"><?php echo $room_notifications[$room]; ?></span>
                        <?php } ?>
                    </a>
                <?php } ?>
            </div>

            <h2>Private Chat</h2>
            <div class="user-grid">
                <?php if (count($chat_users) == 0) { ?>
                    <p class="empty-message">There are no other users yet.</p>
                <?php } ?>

                <?php foreach ($chat_users as $chat_user) { ?>
                    <?php $private_room = privateRoomName($_SESSION["user_id"], $chat_user["id"]); ?>
                    <a class="user-card" href="chartroom.php?user=<?php echo $chat_user["id"]; ?>">
                        <?php echo htmlspecialchars($chat_user["username"]); ?>
                        <?php if (isset($private_notifications[$private_room])) { ?>
                            <span class="room-badge"><?php echo $private_notifications[$private_room]; ?></span>
                        <?php } ?>
                    </a>
                <?php } ?>
            </div>

            <form class="form room-form" method="GET" action="chartroom.php" id="roomForm">
                <label>Custom Room</label>
                <input type="text" name="room" id="roomName" placeholder="Example: dar-class" required>
                <small class="field-error" id="roomNameError"></small>
                <button type="submit">Enter Room</button>
            </form>
        </section>
    </main>
    <script src="asset/chart.js"></script>
</body>
</html>
