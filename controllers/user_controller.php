<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_middleware.php';
require_once __DIR__ . '/../includes/user_queries.php';

header('Content-Type: application/json');

// Ensure the user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

try {
    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token.");
        }

        $userId = $_SESSION['user_id'];
        $name = sanitizeInput($_POST['name']);
        $surname = sanitizeInput($_POST['surname']);
        $email = sanitizeEmail($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address_part1'] . ' ' . $_POST['address_part2']);
        $peselOrId = sanitizeInput($_POST['pesel_or_id']);

        $stmt = $conn->prepare(
            "UPDATE users 
            SET name = ?, surname = ?, email = ?, phone = ?, address = ?, pesel_or_id = ? 
            WHERE id = ?"
        );
        $stmt->bind_param("ssssssi", $name, $surname, $email, $phone, $address, $peselOrId, $userId);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update profile.");
        }

        // Fetch updated details to verify
        $updatedUser = getUserDetails($conn, $userId);
        if (
            $updatedUser['name'] === $name &&
            $updatedUser['surname'] === $surname &&
            $updatedUser['email'] === $email &&
            $updatedUser['phone'] === $phone &&
            $updatedUser['address'] === $address &&
            $updatedUser['pesel_or_id'] === $peselOrId
        ) {
            echo json_encode(['success' => 'Profile updated successfully.']);
        } else {
            throw new Exception("Failed to verify profile update.");
        }
        exit;
    }

    // Handle password change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token.");
        }

        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];

        // Verify current password
        $user = getUserDetails($conn, $userId);
        if (!password_verify($currentPassword, $user['password_hash'])) {
            http_response_code(400);
            throw new Exception("Current password is incorrect.");
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update password.");
        }

        echo json_encode(['success' => 'Password updated successfully.']);
        exit;
    }

} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>

