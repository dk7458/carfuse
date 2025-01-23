<?php
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/user_helpers.php';


header('Content-Type: application/json');

// Ensure the user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak dostępu.']);
    exit;
}

try {
    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Nieprawidłowy token CSRF.");
        }

        $userId = $_SESSION['user_id'];
        $name = sanitizeInput($_POST['name']);
        $surname = sanitizeInput($_POST['surname']);
        $email = sanitizeEmail($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address_part1'] . ' ' . $_POST['address_part2']);
        $peselOrId = sanitizeInput($_POST['pesel_or_id']);

        if (updateUserProfile($conn, $userId, $name, $surname, $email, $phone, $address, $peselOrId)) {
            echo json_encode(['success' => 'Profil został zaktualizowany.']);
        } else {
            throw new Exception("Nie udało się zaktualizować profilu.");
        }
        exit;
    }

    // Handle password change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Nieprawidłowy token CSRF.");
        }

        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];

        if (updateUserPassword($conn, $userId, $currentPassword, $newPassword)) {
            echo json_encode(['success' => 'Hasło zostało zmienione.']);
        } else {
            throw new Exception("Nie udało się zmienić hasła.");
        }
        exit;
    }

} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
