<?php
require '/home/u122931475/domains/carfuse.pl/public_html/../includes/db_connect.php';
require '/home/u122931475/domains/carfuse.pl/public_html/../includes/functions.php';

session_start();

// Ensure the user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = htmlspecialchars($_POST['name']);
    $surname = htmlspecialchars($_POST['surname']);
    $email = sanitizeEmail($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $address = htmlspecialchars($_POST['address_part1'] . ' ' . $_POST['address_part2']);
    $pesel_or_id = htmlspecialchars($_POST['pesel_or_id']);

    $stmt = $conn->prepare("
        UPDATE users 
        SET name = ?, surname = ?, email = ?, phone = ?, address = ?, pesel_or_id = ? 
        WHERE id = ?
    ");
    $stmt->bind_param("ssssssi", $name, $surname, $email, $phone, $address, $pesel_or_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        // Verify the update
        $stmt = $conn->prepare("SELECT name, surname, email, phone, address, pesel_or_id FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $updatedUser = $stmt->get_result()->fetch_assoc();

        if ($updatedUser['name'] === $name && $updatedUser['surname'] === $surname && $updatedUser['email'] === $email && $updatedUser['phone'] === $phone && $updatedUser['address'] === $address && $updatedUser['pesel_or_id'] === $pesel_or_id) {
            echo json_encode(['success' => 'Profile updated successfully.']);
        } else {
            echo json_encode(['error' => 'Failed to verify profile update.']);
        }
    } else {
        echo json_encode(['error' => 'Failed to update profile.']);
    }
    exit;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];

    // Verify current password
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!password_verify($currentPassword, $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Current password is incorrect.']);
        exit;
    }

    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Password updated successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to update password.']);
    }
    exit;
}
?>
