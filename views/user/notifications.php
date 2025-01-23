<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

// File Path: /views/user/notifications.php
// Description: Allows users to manage their notification preferences and view past notifications.
// Changelog:
// - Added functionality to display past notifications.
// - Improved the user interface with a separate section for notification history.

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


$userId = $_SESSION['user_id'];

// Fetch user notification preferences
$preferences = $conn->query("SELECT email_notifications, sms_notifications FROM users WHERE id = $userId")->fetch_assoc();

// Handle POST request to update notification preferences
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET email_notifications = ?, sms_notifications = ? WHERE id = ?");
    $stmt->bind_param("iii", $emailNotifications, $smsNotifications, $userId);

    if ($stmt->execute()) {
        $successMessage = "Preferencje powiadomień zostały zaktualizowane.";
    } else {
        $errorMessage = "Wystąpił błąd podczas zapisywania preferencji.";
    }
}

// Fetch past notifications for the user
$notifications = $conn->query("
    SELECT id, type, message, sent_at 
    FROM notifications 
    WHERE user_id = $userId 
    ORDER BY sent_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia Powiadomień</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
</head>
<body>
    <?php include '../shared/navbar_user.php'; ?>

    <div class="container">
        <h2 class="mt-5">Ustawienia Powiadomień</h2>
        <p class="text-center">Zarządzaj swoimi preferencjami powiadomień:</p>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php elseif (isset($errorMessage)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <div class="form-check mb-3">
                <input type="checkbox" id="email_notifications" name="email_notifications" class="form-check-input" 
                    <?= $preferences['email_notifications'] ? 'checked' : ''; ?>>
                <label for="email_notifications" class="form-check-label">Otrzymuj powiadomienia e-mail</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" id="sms_notifications" name="sms_notifications" class="form-check-input" 
                    <?= $preferences['sms_notifications'] ? 'checked' : ''; ?>>
                <label for="sms_notifications" class="form-check-label">Otrzymuj powiadomienia SMS</label>
            </div>
            <button type="submit" class="btn btn-primary">Zapisz</button>
        </form>

        <div class="mt-5">
            <h3>Historia Powiadomień</h3>
            <?php if (!empty($notifications)): ?>
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Typ</th>
                            <th>Wiadomość</th>
                            <th>Data Wysłania</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification): ?>
                            <tr>
                                <td><?= htmlspecialchars($notification['id']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($notification['type'])) ?></td>
                                <td><?= htmlspecialchars($notification['message']) ?></td>
                                <td><?= htmlspecialchars(date('d-m-Y H:i', strtotime($notification['sent_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">Brak powiadomień w historii.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
