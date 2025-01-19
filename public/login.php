<?php
require '../includes/db_connect.php';
session_start();

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Nieprawidłowy adres e-mail.";
    } else {
        $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($userId, $hashedPassword);
            $stmt->fetch();
            if (password_verify($password, $hashedPassword)) {
                $_SESSION['user_id'] = $userId;
                header("Location: /public/user/dashboard.php");
                exit;
            } else {
                $_SESSION['error_message'] = "Nieprawidłowe hasło.";
            }
        } else {
            $_SESSION['error_message'] = "Nie znaleziono konta z tym adresem e-mail.";
        }
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
    <link href="../styles/settings.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4 standard-form">
            <h1 class="text-center mb-4">Logowanie</h1>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Hasło</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <a href="reset_password.php" class="btn btn-link">Zapomniałeś hasła?</a>
                    <a href="register.php" class="btn btn-link">Utwórz konto</a>
                </div>
                <button type="submit" class="btn btn-primary w-100">Zaloguj się</button>
            </form>
        </div>
    </div>
</body>

</html>
