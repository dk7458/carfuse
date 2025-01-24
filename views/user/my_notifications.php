<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/user/my_notifications.php
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'functions/global.php';
require_once BASE_PATH . 'functions/notification.php';


$userId = $_SESSION['user_id'];

// Fetch user's notifications
$stmt = $conn->prepare("SELECT type, message, sent_at FROM notifications WHERE user_id = ? ORDER BY sent_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje Powiadomienia</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
</head>
<body>
    <?php include '../shared/navbar_user.php'; ?>

    <div class="container">
        <h1 class="mt-5">Moje Powiadomienia</h1>

        <div class="mt-4">
            <a href="/views/user/notifications.php" class="btn btn-link">Zarządzaj Preferencjami Powiadomień</a>
        </div>

        <?php if ($notifications->num_rows > 0): ?>
            <ul class="list-group mt-4">
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <li class="list-group-item">
                        <strong><?= $notification['type'] === 'email' ? 'E-mail' : 'SMS' ?>:</strong>
                        <p><?= htmlspecialchars($notification['message']) ?></p>
                        <small class="text-muted">Wysłano: <?= htmlspecialchars($notification['sent_at']) ?></small>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <div class="alert alert-info mt-4">Nie masz żadnych powiadomień.</div>
        <?php endif; ?>
    </div>
</body>
</html>
