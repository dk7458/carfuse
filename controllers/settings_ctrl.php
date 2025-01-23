$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /controllers/settings_ctrl.php
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


// Ensure the user has the required role
enforceRole(['admin', 'super_admin'], '/public/login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin database transaction
        $conn->begin_transaction();

        foreach ($_POST as $key => $value) {
            $sanitizedKey = htmlspecialchars($key);
            $sanitizedValue = htmlspecialchars(trim($value));

            // Validate settings values
            if (empty($sanitizedValue)) {
                throw new Exception("Field '$sanitizedKey' cannot be empty.");
            }

            // Specific validations for known settings
            if ($sanitizedKey === 'tax_rate' && (!is_numeric($sanitizedValue) || $sanitizedValue < 0 || $sanitizedValue > 100)) {
                throw new Exception("Tax rate must be a number between 0 and 100.");
            }

            if ($sanitizedKey === 'email_notifications' && !in_array($sanitizedValue, ['0', '1'])) {
                throw new Exception("Invalid value for email notifications.");
            }

            if ($sanitizedKey === 'sms_notifications' && !in_array($sanitizedValue, ['0', '1'])) {
                throw new Exception("Invalid value for SMS notifications.");
            }

            // Insert or update the setting in the database
            $stmt = $conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->bind_param("ss", $sanitizedKey, $sanitizedValue);
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        // Log the settings update
        logAction($_SESSION['user_id'], 'update_settings', 'System settings updated.');

        // Success message
        $_SESSION['success_message'] = "Settings updated successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        logError($e->getMessage());
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    } finally {
        // Redirect back to the settings page
        header("Location: /views/admin/settings.php");
        exit;
    }
} else {
    // Handle invalid request method
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}
