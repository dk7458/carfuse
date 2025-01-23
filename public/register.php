<?php
// File Path: /public/register.php
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/functions.php';


/**
 * Create user directories for uploads and documents.
 *
 * @param int $userId
 */
function createUserDirectories($userId) {
    $baseDir = $_SERVER['DOCUMENT_ROOT'] . "/users/user$userId";
    $uploadsDir = "$baseDir/uploads";
    $documentsDir = "$baseDir/documents";

    // Ensure the base directory exists
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);
    }

    // Create subdirectories
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    if (!is_dir($documentsDir)) {
        mkdir($documentsDir, 0755, true);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $surname = htmlspecialchars(trim($_POST['surname']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    $phone = htmlspecialchars(trim($_POST['phone']));

    // Validate input
    if (empty($name) || empty($surname) || empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Wszystkie pola są wymagane.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Podano nieprawidłowy adres e-mail.";
    } elseif (strlen($password) < 8) {
        $_SESSION['error_message'] = "Hasło musi mieć co najmniej 8 znaków.";
    } else {
        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Check if the email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $_SESSION['error_message'] = "Ten adres e-mail jest już zarejestrowany.";
            } else {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (name, surname, email, password_hash, phone) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $surname, $email, $passwordHash, $phone);

                if ($stmt->execute()) {
                    $userId = $stmt->insert_id;

                    // Create user directories
                    createUserDirectories($userId);

                    // Log registration action
                    logAction($userId, 'registration', 'Użytkownik zarejestrowany.');

                    // Success message and redirection
                    $_SESSION['success_message'] = "Rejestracja zakończona sukcesem. Możesz się teraz zalogować.";
                    header("Location: login.php");
                    exit;
                } else {
                    throw new Exception("Wystąpił błąd podczas rejestracji użytkownika.");
                }
            }
        } catch (Exception $e) {
            logError($e->getMessage());
            $_SESSION['error_message'] = "Wystąpił błąd podczas rejestracji. Spróbuj ponownie.";
        } finally {
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <style>
        .container {
            max-width: 500px;
        }

        .form-control {
            max-width: 450px;
        }
    </style>
</head>
<body>
    <?php include '../views/shared/navbar_empty.php'; ?>

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4 standard-form">
            <h1 class="text-center mb-4">Rejestracja</h1>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error_message']); ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success_message']); ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="mb-3">
                    <label for="name" class="form-label">Imię</label>
                    <input type="text" id="name" name="name" class="form-control mx-auto" required>
                </div>
                <div class="mb-3">
                    <label for="surname" class="form-label">Nazwisko</label>
                    <input type="text" id="surname" name="surname" class="form-control mx-auto" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control mx-auto" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Hasło</label>
                    <input type="password" id="password" name="password" class="form-control mx-auto" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Numer telefonu (opcjonalnie)</label>
                    <input type="text" id="phone" name="phone" class="form-control mx-auto">
                </div>
                <button type="submit" class="btn btn-primary w-100">Zarejestruj się</button>
            </form>

            <p class="text-center mt-3">
                Masz już konto? <a href="login.php">Zaloguj się</a>
            </p>
        </div>
    </div>
</body>
</html>
