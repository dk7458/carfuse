$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

// Fetch user notification preferences
$userId = $_SESSION['user_id'];
$preferences = $conn->query("SELECT email_notifications, sms_notifications FROM users WHERE id = $userId")->fetch_assoc();

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
?>

<div class="container">
    <h2 class="mt-5">Ustawienia Powiadomień</h2>
    <p class="text-center">Zarządzaj swoimi preferencjami powiadomień:</p>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php elseif (isset($errorMessage)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-4">
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

