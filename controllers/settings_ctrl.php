<?php
// File Path: /controllers/settings_ctrl.php
require_once __DIR__ . '/../includes/session_middleware.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure the user is an admin
if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Brak dostępu.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taxRate = $_POST['tax_rate'] ?? null;
    $emailNotifications = $_POST['email_notifications'] ?? null;
    $smsNotifications = $_POST['sms_notifications'] ?? null;

    if ($taxRate === null || $emailNotifications === null || $smsNotifications === null) {
        $_SESSION['error_message'] = "Wszystkie pola są wymagane.";
        header("Location: /views/admin/settings.php");
        exit;
    }

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Update tax rate
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                                VALUES ('tax_rate', ?) 
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param("s", $taxRate);
        $stmt->execute();

        // Update email notifications
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                                VALUES ('email_notifications', ?) 
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param("s", $emailNotifications);
        $stmt->execute();

        // Update SMS notifications
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                                VALUES ('sms_notifications', ?) 
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param("s", $smsNotifications);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $_SESSION['success_message'] = "Ustawienia zostały zaktualizowane pomyślnie.";
    } catch (Exception $e) {
        $conn->rollback();
        logError($e->getMessage());
        $_SESSION['error_message'] = "Wystąpił błąd podczas zapisywania ustawień.";
    } finally {
        header("Location: /views/admin/settings.php");
        exit;
    }
}
