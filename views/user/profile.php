<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/user/profile.php

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

// Fetch user details
$userId = $_SESSION['user_id'];
$userDetails = $conn->query("SELECT name, surname, email, address, pesel_or_id, phone FROM users WHERE id = $userId")->fetch_assoc();

$successMessage = $errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $errorMessage = "Nieprawidłowy token CSRF.";
    } else {
        $name = sanitizeInput($_POST['name']);
        $surname = sanitizeInput($_POST['surname']);
        $email = sanitizeEmail($_POST['email']);
        $address = sanitizeInput($_POST['address']);
        $peselOrId = sanitizeInput($_POST['pesel_or_id']);
        $phone = sanitizeInput($_POST['phone']);

        // Validate input
        if (!$email) {
            $errorMessage = "Nieprawidłowy adres e-mail.";
        } elseif (strlen($phone) < 9 || !ctype_digit($phone)) {
            $errorMessage = "Nieprawidłowy numer telefonu.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET name = ?, surname = ?, email = ?, address = ?, pesel_or_id = ?, phone = ? WHERE id = ?"
            );
            $stmt->bind_param("ssssssi", $name, $surname, $email, $address, $peselOrId, $phone, $userId);

            if ($stmt->execute()) {
                $successMessage = "Dane osobowe zostały zaktualizowane pomyślnie.";
                $userDetails = $conn->query("SELECT name, surname, email, address, pesel_or_id, phone FROM users WHERE id = $userId")->fetch_assoc();
            } else {
                $errorMessage = "Wystąpił błąd podczas aktualizacji danych.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twój Profil</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
</head>
<body>
    <?php include '../shared/navbar_user.php'; ?>

    <div class="container">
        <h2 class="mt-5">Twój Profil</h2>

        <div class="card p-4">
            <h3>Dane Osobowe</h3>
            <p><strong>Imię:</strong> <?= htmlspecialchars($userDetails['name']); ?></p>
            <p><strong>Nazwisko:</strong> <?= htmlspecialchars($userDetails['surname']); ?></p>
            <p><strong>E-mail:</strong> <?= htmlspecialchars($userDetails['email']); ?></p>
            <p><strong>Adres:</strong> <?= htmlspecialchars($userDetails['address']); ?></p>
            <p><strong>PESEL lub Numer Dowodu:</strong> <?= htmlspecialchars($userDetails['pesel_or_id']); ?></p>
            <p><strong>Telefon:</strong> <?= htmlspecialchars($userDetails['phone']); ?></p>
        </div>

        <h3 class="mt-5">Zmień Dane Osobowe</h3>

        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage); ?></div>
        <?php elseif ($errorMessage): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">

            <div class="mb-3">
                <label for="name" class="form-label">Imię</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($userDetails['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="surname" class="form-label">Nazwisko</label>
                <input type="text" id="surname" name="surname" class="form-control" value="<?= htmlspecialchars($userDetails['surname']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($userDetails['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Adres</label>
                <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($userDetails['address']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="pesel_or_id" class="form-label">PESEL lub Numer Dowodu</label>
                <input type="text" id="pesel_or_id" name="pesel_or_id" class="form-control" value="<?= htmlspecialchars($userDetails['pesel_or_id']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Telefon</label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($userDetails['phone']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Zapisz Zmiany</button>
        </form>
    </div>
</body>
</html>
