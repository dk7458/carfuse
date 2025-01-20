<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

// Fetch user details
$userId = $_SESSION['user_id'];
$userDetails = $conn->query("SELECT name, surname, email, address, pesel_or_id, phone FROM users WHERE id = $userId")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $peselOrId = $_POST['pesel_or_id'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Nieprawidłowy adres e-mail.";
    } elseif (strlen($phone) < 9 || !ctype_digit($phone)) {
        $errorMessage = "Nieprawidłowy numer telefonu.";
    } else {
        // Update user details
        $stmt = $conn->prepare("UPDATE users SET name = ?, surname = ?, email = ?, address = ?, pesel_or_id = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $surname, $email, $address, $peselOrId, $phone, $userId);

        if ($stmt->execute()) {
            $successMessage = "Dane osobowe zostały zaktualizowane pomyślnie.";
            // Refresh user details
            $userDetails = $conn->query("SELECT name, surname, email, address, pesel_or_id, phone FROM users WHERE id = $userId")->fetch_assoc();
        } else {
            $errorMessage = "Wystąpił błąd podczas aktualizacji danych.";
        }
    }
}
?>

<div class="container">
    <h2 class="mt-5">Twój Profil</h2>
    <div class="card p-4">
        <h3>Dane Osobowe</h3>
        <p><strong>Imię:</strong> <span><?php echo htmlspecialchars($userDetails['name']); ?></span></p>
        <p><strong>Nazwisko:</strong> <span><?php echo htmlspecialchars($userDetails['surname']); ?></span></p>
        <p><strong>E-mail:</strong> <span><?php echo htmlspecialchars($userDetails['email']); ?></span></p>
        <p><strong>Adres:</strong> <span><?php echo htmlspecialchars($userDetails['address']); ?></span></p>
        <p><strong>PESEL lub Numer Dowodu:</strong> <span><?php echo htmlspecialchars($userDetails['pesel_or_id']); ?></span></p>
        <p><strong>Telefon:</strong> <span><?php echo htmlspecialchars($userDetails['phone']); ?></span></p>
    </div>

    <h3 class="mt-5">Zmień Dane Osobowe</h3>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php elseif (isset($errorMessage)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-4">
        <div class="mb-3">
            <label for="name" class="form-label">Imię</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($userDetails['name']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="surname" class="form-label">Nazwisko</label>
            <input type="text" id="surname" name="surname" class="form-control" value="<?php echo htmlspecialchars($userDetails['surname']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userDetails['email']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Adres</label>
            <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($userDetails['address']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="pesel_or_id" class="form-label">PESEL lub Numer Dowodu</label>
            <input type="text" id="pesel_or_id" name="pesel_or_id" class="form-control" value="<?php echo htmlspecialchars($userDetails['pesel_or_id']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Telefon</label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($userDetails['phone']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Zapisz Zmiany</button>
    </form>
</div>

