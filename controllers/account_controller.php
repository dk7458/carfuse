<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/user_queries.php';


header('Content-Type: application/json');

enforceRole('user', '/public/no_access.php'); // Redirect unauthorized users to a custom page


try {
    $userId = $_SESSION['user_id'];

    // Handle account deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token.");
        }

        $confirmation = $_POST['confirmation'] ?? '';

        if ($confirmation !== 'DELETE') {
            throw new Exception("You must type DELETE to confirm account deletion.");
        }

        // Begin transaction
        $conn->begin_transaction();
        try {
            // Delete bookings
            $stmt = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Delete payment methods
            $stmt = $conn->prepare("DELETE FROM payment_methods WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Delete notifications
            $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Delete logs
            $stmt = $conn->prepare("DELETE FROM logs WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Delete user documents and uploads
            $userBaseDir = $_SERVER['DOCUMENT_ROOT'] . "/users/user$userId";

            foreach (['documents', 'uploads'] as $subDir) {
                $subPath = "$userBaseDir/$subDir";
                if (is_dir($subPath)) {
                    $files = glob("$subPath/*");
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                    rmdir($subPath);
                }
            }

            if (is_dir($userBaseDir)) {
                rmdir($userBaseDir);
            }

            // Delete the user account
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            // Log out and redirect
            session_destroy();
            echo json_encode(['success' => 'Account deleted successfully.']);
        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception("Failed to delete account: " . $e->getMessage());
        }
        exit;
    }

} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
