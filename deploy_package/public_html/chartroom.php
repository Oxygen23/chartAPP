<?php
session_start();
include "connection.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

checkSessionTimeout();

$current_user_id = $_SESSION["user_id"];
$room_title = "Room";
$is_private_room = false;
$private_user_id = 0;

function privateRoomName($first_user_id, $second_user_id) {
    $small_id = min($first_user_id, $second_user_id);
    $big_id = max($first_user_id, $second_user_id);

    return "private_" . $small_id . "_" . $big_id;
}

if (isset($_GET["user"])) {
    $private_user_id = (int) $_GET["user"];

    if ($private_user_id == $current_user_id || $private_user_id < 1) {
        header("Location: dashboard.php");
        exit();
    }

    $user_check = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $user_check->execute([$private_user_id]);
    $private_user = $user_check->fetch();

    if (!$private_user) {
        header("Location: dashboard.php");
        exit();
    }

    $room = privateRoomName($current_user_id, $private_user_id);
    $room_title = "Private: " . $private_user["username"];
    $is_private_room = true;
} else {
    $room = isset($_GET["room"]) ? trim($_GET["room"]) : "general";
    $room = strtolower($room);
    $room = preg_replace("/[^a-z0-9_-]/", "", $room);

    if ($room == "") {
        $room = "general";
    }

    if (strpos($room, "private_") === 0) {
        $parts = explode("_", $room);

        if (count($parts) != 3 || !is_numeric($parts[1]) || !is_numeric($parts[2])) {
            header("Location: dashboard.php");
            exit();
        }

        $first_user_id = (int) $parts[1];
        $second_user_id = (int) $parts[2];

        if ($current_user_id != $first_user_id && $current_user_id != $second_user_id) {
            header("Location: dashboard.php");
            exit();
        }

        $private_user_id = $current_user_id == $first_user_id ? $second_user_id : $first_user_id;
        $user_check = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
        $user_check->execute([$private_user_id]);
        $private_user = $user_check->fetch();

        if (!$private_user) {
            header("Location: dashboard.php");
            exit();
        }

        $room = privateRoomName($first_user_id, $second_user_id);
        $room_title = "Private: " . $private_user["username"];
        $is_private_room = true;
    } else {
        $room_title = "Room: " . $room;
    }
}

$error = "";
$edit_id = isset($_GET["edit"]) ? (int) $_GET["edit"] : 0;

if (isset($_POST["send_message"])) {
    $message = trim($_POST["message"]);
    $user_id = $current_user_id;

    if ($message == "") {
        $error = "Message cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (user_id, room_name, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $room, $message]);
        $message_id = $conn->lastInsertId();

        $notify = $conn->prepare("
            INSERT INTO notifications (user_id, message_id, room_name)
            VALUES (?, ?, ?)
        ");

        if ($is_private_room) {
            $notify->execute([$private_user_id, $message_id, $room]);
        } else {
            $users = $conn->prepare("SELECT id FROM users WHERE id != ?");
            $users->execute([$user_id]);
            $other_users = $users->fetchAll();

            foreach ($other_users as $other_user) {
                $notify->execute([$other_user["id"], $message_id, $room]);
            }
        }

        header("Location: chartroom.php?room=" . urlencode($room));
        exit();
    }
}

if (isset($_POST["update_message"])) {
    $message_id = (int) $_POST["message_id"];
    $message = trim($_POST["message"]);
    $user_id = $_SESSION["user_id"];

    if ($message == "") {
        $error = "Message cannot be empty.";
        $edit_id = $message_id;
    } else {
        $stmt = $conn->prepare("
            UPDATE messages
            SET message = ?
            WHERE id = ? AND user_id = ? AND room_name = ?
        ");
        $stmt->execute([$message, $message_id, $user_id, $room]);

        header("Location: chartroom.php?room=" . urlencode($room));
        exit();
    }
}

if (isset($_POST["delete_message"])) {
    $message_id = (int) $_POST["message_id"];
    $user_id = $_SESSION["user_id"];

    $stmt = $conn->prepare("
        DELETE FROM messages
        WHERE id = ? AND user_id = ? AND room_name = ?
    ");
    $stmt->execute([$message_id, $user_id, $room]);

    header("Location: chartroom.php?room=" . urlencode($room));
    exit();
}

$read = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE user_id = ? AND room_name = ?
");
$read->execute([$_SESSION["user_id"], $room]);

$notify_count = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = ? AND is_read = 0
");
$notify_count->execute([$_SESSION["user_id"]]);
$unread_notifications = $notify_count->fetch()["total"];

$stmt = $conn->prepare("
    SELECT messages.id, messages.user_id, messages.message, messages.created_at, users.username
    FROM messages
    JOIN users ON messages.user_id = users.id
    WHERE messages.room_name = ?
    ORDER BY messages.created_at ASC
");
$stmt->execute([$room]);
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room_title); ?> - Chat Room</title>
    <link rel="stylesheet" href="asset/chart.css">
</head>
<body data-session-timeout="180">
    <header class="topbar">
        <div>
            <strong><?php echo htmlspecialchars($room_title); ?></strong>
            <span><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        </div>
        <nav class="top-links">
            <a href="dashboard.php">Rooms <?php if ($unread_notifications > 0) { ?><span class="notify-badge"><?php echo $unread_notifications; ?></span><?php } ?></a>
            <a href="login.php?logout=1">Logout</a>
        </nav>
    </header>

    <main class="chat-page">
        <section class="chat-box">
            <div class="messages" id="messagesBox">
                <?php if (count($messages) == 0) { ?>
                    <p class="empty-message">There are no messages yet. Start the conversation.</p>
                <?php } ?>

                <?php foreach ($messages as $row) { ?>
                    <?php $is_me = $row["user_id"] == $_SESSION["user_id"]; ?>
                    <article class="message <?php echo $is_me ? "mine" : ""; ?>">
                        <div class="message-name">
                            <?php echo htmlspecialchars($row["username"]); ?>
                            <span><?php echo date("H:i", strtotime($row["created_at"])); ?></span>
                        </div>

                        <?php if ($is_me && $edit_id == $row["id"]) { ?>
                            <form method="POST" class="edit-message-form">
                                <input type="hidden" name="message_id" value="<?php echo $row["id"]; ?>">
                                <textarea name="message" required><?php echo htmlspecialchars($row["message"]); ?></textarea>
                                <div class="message-actions">
                                    <button type="submit" name="update_message" class="small-button">Save</button>
                                    <a class="small-link" href="chartroom.php?room=<?php echo urlencode($room); ?>">Cancel</a>
                                </div>
                            </form>
                        <?php } else { ?>
                            <p><?php echo nl2br(htmlspecialchars($row["message"])); ?></p>
                        <?php } ?>

                        <?php if ($is_me && $edit_id != $row["id"]) { ?>
                            <div class="message-actions">
                                <a class="small-link" href="chartroom.php?room=<?php echo urlencode($room); ?>&edit=<?php echo $row["id"]; ?>">Edit</a>

                                <form method="POST" class="delete-message-form">
                                    <input type="hidden" name="message_id" value="<?php echo $row["id"]; ?>">
                                    <button type="submit" name="delete_message" class="delete-button">Delete</button>
                                </form>
                            </div>
                        <?php } ?>
                    </article>
                <?php } ?>
            </div>

            <?php if ($error != "") { ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>

            <form method="POST" class="message-form" id="messageForm">
                <textarea name="message" id="messageInput" placeholder="Write a message..." required></textarea>
                <small class="field-error" id="messageError"></small>
                <button type="submit" name="send_message">Send</button>
            </form>
        </section>
    </main>
    <script src="asset/chart.js"></script>
</body>
</html>
