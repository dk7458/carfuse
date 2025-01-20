

<?php
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch current preferences
$result = $conn->query("SELECT * FROM admin_notification_settings WHERE admin_id = {$_SESSION['user_id']} LIMIT 1");
$settings = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractAlerts = isset($_POST['contract_alerts']) ? 1 : 0;
    $maintenanceAlerts = isset($_POST['maintenance_alerts']) ? 1 : 0;
    $bookingReminders = isset($_POST['booking_reminders']) ? 1 : 0;

    if ($settings) {
        // Update existing preferences
        $stmt = $conn->prepare("
            UPDATE admin_notification_settings 
            SET contract_alerts = ?, maintenance_alerts = ?, booking_reminders = ?
            WHERE admin_id = ?
        ");
        $stmt->bind_param("iiii", $contractAlerts, $maintenanceAlerts, $bookingReminders, $_SESSION['user_id']);
    } else {
        // Insert new preferences
        $stmt = $conn->prepare("
            INSERT INTO admin_notification_settings (admin_id, contract_alerts, maintenance_alerts, booking_reminders) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiii", $_SESSION['user_id'], $contractAlerts, $maintenanceAlerts, $bookingReminders);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Preferencje powiadomień zostały zapisane.";
    } else {
        $_SESSION['error_message'] = "Wystąpił błąd podczas zapisywania preferencji.";
    }

    redirect('/public/admin/dashboard.php?page=powiadomienia');
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
    <div class="container mt-5">
        <h1 class="text-center">Ustawienia Powiadomień</h1>

        <?php include '/home/u122931475/domains/carfuse.pl/public_html/views/shared/messages.php'; ?>

        <form method="POST" action="" class="standard-form">
            <div class="form-check mb-3">
                <input type="checkbox" id="contract_alerts" name="contract_alerts" class="form-check-input"
                    <?php echo isset($settings['contract_alerts']) && $settings['contract_alerts'] ? 'checked' : ''; ?>>
                <label for="contract_alerts" class="form-check-label">Powiadomienia o wygaśnięciu umów</label>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" id="maintenance_alerts" name="maintenance_alerts" class="form-check-input"
                    <?php echo isset($settings['maintenance_alerts']) && $settings['maintenance_alerts'] ? 'checked' : ''; ?>>
                <label for="maintenance_alerts" class="form-check-label">Alerty o konserwacji</label>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" id="booking_reminders" name="booking_reminders" class="form-check-input"
                    <?php echo isset($settings['booking_reminders']) && $settings['booking_reminders'] ? 'checked' : ''; ?>>
                <label for="booking_reminders" class="form-check-label">Przypomnienia o rezerwacjach</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">Zapisz</button>
        </form>
    </div>
</body>
</html>
