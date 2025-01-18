<?php
require __DIR__ .  '../includes/db_connect.php';
require __DIR__ .  '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = sanitizeEmail($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $_SESSION['error_message'] = "Wszystkie pola są wymagane.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Nieprawidłowy format e-mail.";
    } elseif ($password !== $confirmPassword) {
        $_SESSION['error_message'] = "Hasła nie pasują do siebie.";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
        $_SESSION['error_message'] = "Hasło musi mieć co najmniej 8 znaków, zawierać litery i cyfry.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $checkUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkUser->bind_param("s", $email);
        $checkUser->execute();
        $checkUser->store_result();

        if ($checkUser->num_rows > 0) {
            $_SESSION['error_message'] = "E-mail jest już zarejestrowany.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $passwordHash);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Rejestracja zakończona sukcesem. Możesz się zalogować.";
                redirect('/public/login.php');
            } else {
                $_SESSION['error_message'] = "Wystąpił błąd podczas rejestracji.";
            }
        }
    }
}
?>
