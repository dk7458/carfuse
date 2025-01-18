<?php
require '../includes/db_connect.php';
require '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeEmail($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($userId, $passwordHash, $role);
    $stmt->fetch();

    if ($userId && password_verify($password, $passwordHash)) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $role;
        redirect($role === 'admin' ? '/views/admin/dashboard.php' : '/public/profile.php');
    } else {
        $_SESSION['error_message'] = "Nieprawidłowe dane logowania.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Zaloguj się</h1>
        <?php include '../views/shared/messages.php'; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Hasło</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Zaloguj się</button>
        </form>
    </div>
</body>
</html>
