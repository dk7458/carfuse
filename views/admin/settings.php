<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/admin/settings.php
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'functions/global.php';


enforceRole(['admin', 'super_admin']); 

// Fetch current settings
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings");
$stmt->execute();
$result = $stmt->get_result();
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia Systemowe</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <h1>Ustawienia Systemowe</h1>
        <p class="text-muted">Zarządzaj preferencjami systemowymi i ustawieniami powiadomień.</p>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/controllers/settings_ctrl.php">
            <div class="mb-3">
                <label for="tax_rate" class="form-label">Stawka Podatku VAT (%)</label>
                <input 
                    type="number" 
                    id="tax_rate" 
                    name="tax_rate" 
                    class="form-control" 
                    value="<?= htmlspecialchars($settings['tax_rate'] ?? 23); ?>" 
                    required 
                    min="0" 
                    max="100"
                >
            </div>
            <div class="mb-3">
                <label for="email_notifications" class="form-label">Domyślne Powiadomienia E-mail</label>
                <select id="email_notifications" name="email_notifications" class="form-select">
                    <option value="1" <?= isset($settings['email_notifications']) && $settings['email_notifications'] === '1' ? 'selected' : ''; ?>>Włączone</option>
                    <option value="0" <?= isset($settings['email_notifications']) && $settings['email_notifications'] === '0' ? 'selected' : ''; ?>>Wyłączone</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="sms_notifications" class="form-label">Domyślne Powiadomienia SMS</label>
                <select id="sms_notifications" name="sms_notifications" class="form-select">
                    <option value="1" <?= isset($settings['sms_notifications']) && $settings['sms_notifications'] === '1' ? 'selected' : ''; ?>>Włączone</option>
                    <option value="0" <?= isset($settings['sms_notifications']) && $settings['sms_notifications'] === '0' ? 'selected' : ''; ?>>Wyłączone</option>
                </select>
            </div>
            <!-- Placeholder for additional settings -->
            <div class="mb-3">
                <label for="other_setting" class="form-label">Inne Ustawienie (Przykład)</label>
                <input 
                    type="text" 
                    id="other_setting" 
                    name="other_setting" 
                    class="form-control" 
                    placeholder="Przykładowe ustawienie" 
                    value="<?= htmlspecialchars($settings['other_setting'] ?? ''); ?>"
                >
            </div>
            <button type="submit" class="btn btn-primary">Zapisz Zmiany</button>
        </form>
    </div>
</body>
</html>
