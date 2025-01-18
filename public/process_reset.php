<?php
require __DIR__ . '../includes/db_connect.php';
require __DIR__ . '../includes/functions.php';

session_start();

$token = $_GET['token'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error_message'] = "Wszystkie pola są wymagane.";
    } elseif ($newPassword !== $confirmPassword) {
        $_SESSION['error_message'] = "Hasła nie pasują do siebie.";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $newPassword)) {
        $_SESSION['error_message'] = "Hasło musi mieć co najmniej 8 znaków, zawierać litery i cyfry.";
    } else {
        // Verify the token
        $stmt = $conn->prepare("
            SELECT email, expires_at 
            FROM password_resets 
            WHERE token = ?
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0 || strtotime($stmt->fetch_assoc()['expires_at']) < time()) {
            $_SESSION['error_message'] = "Token jest nieprawidłowy lub wygasł.";
        } else {
            $stmt->bind_result($email, $expiresAt);
            $stmt->fetch();

            // Update the password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updatePassword = $conn->prepare("
                UPDATE users SET password_hash = ? WHERE email = ?
            ");
            $updatePassword->bind_param("ss", $hashedPassword, $email);

            if ($updatePassword->execute()) {
                $_SESSION['success_message'] = "Hasło zostało zresetowane. Możesz się teraz zalogować.";
                redirect('/public/login.php');
            } else {
                $_SESSION['error_message'] = "Wystąpił błąd podczas resetowania hasła.";
            }
        }
    }
}
?>
