<?php

function updateUserProfile($conn, $userId, $name, $surname, $email, $phone, $address, $peselOrId) {
    $stmt = $conn->prepare("
        UPDATE users 
        SET name = ?, surname = ?, email = ?, phone = ?, address = ?, pesel_or_id = ? 
        WHERE id = ?
    ");
    $stmt->bind_param("ssssssi", $name, $surname, $email, $phone, $address, $peselOrId, $userId);
    return $stmt->execute();
}

function updateUserPassword($conn, $userId, $currentPassword, $newPassword) {
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!password_verify($currentPassword, $user['password_hash'])) {
        return false;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    return $stmt->execute();
}

function deleteUser($conn, $userId) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

function updateUserRole($conn, $userId, $role) {
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $userId);
    return $stmt->execute();
}

?>
