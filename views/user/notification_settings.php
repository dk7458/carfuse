<?php
require '/home/u122931475/domains/carfuse.pl/public_html/../includes/db_connect.php';
require '/home/u122931475/domains/carfuse.pl/public_html/../includes/functions.php';

session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

$userId = $_SESSION['user_id'];

// Fetch current preferences
$result = $conn->query("SELECT email_notifications, sms_notifications FROM users WHERE id = $userId");
$preferences = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET email_notifications = ?, sms_notifications = ? WHERE id = ?");
    $stmt->bind_param("iii", $emailNotifications, $smsNotifications, $userId);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Preferencje powiadomień zostały zaktualizowane.";
        redirect('/views/user/notification_settings.php');
    } else {
        $_SESSION['error_message'] = "Wystąpił błąd podczas zapisywania preferencji.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia Powiadomień</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">

</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Ustawienia Powiadomień</h1>

        <form method="POST" action="" class="standard-form">
            <div class="form-check mb-3">
                <input type="checkbox" id="email_notifications" name="email_notifications" class="form-check-input" 
                    <?php echo $preferences['email_notifications'] ? 'checked' : ''; ?>>
                <label for="email_notifications" class="form-check-label">Otrzymuj powiadomienia e-mail</label>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" id="sms_notifications" name="sms_notifications" class="form-check-input" 
                    <?php echo $preferences['sms_notifications'] ? 'checked' : ''; ?>>
                <label for="sms_notifications" class="form-check-label">Otrzymuj powiadomienia SMS</label>
            </div>

            <button type="submit" class="btn btn-primary">Zapisz</button>
        </form>
    </div>
</body>
</html>
