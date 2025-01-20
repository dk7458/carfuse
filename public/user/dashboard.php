<?php
require '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/login.php');
}

// Set session timeout
$timeout = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    redirect('/login.php');
}
$_SESSION['last_activity'] = time();

$userId = $_SESSION['user_id'];

// Ensure user document directory exists
$userDocumentDir = "../../uploads/users/$userId";
if (!is_dir($userDocumentDir)) {
    mkdir($userDocumentDir, 0777, true);
}

// Fetch user bookings
$bookings = $conn->query("
    SELECT b.id, f.make, f.model, f.registration_number, b.pickup_date, b.dropoff_date, b.total_price, b.status, b.rental_contract_pdf 
    FROM bookings b 
    JOIN fleet f ON b.vehicle_id = f.id 
    WHERE b.user_id = $userId
    ORDER BY b.created_at DESC
");

// Fetch user details
$userDetails = $conn->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();

// Fetch notification preferences
$preferences = $conn->query("SELECT email_notifications, sms_notifications FROM users WHERE id = $userId")->fetch_assoc();

// Fetch user documents
$userDocuments = glob("$userDocumentDir/*.{pdf}", GLOB_BRACE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET email_notifications = ?, sms_notifications = ? WHERE id = ?");
    $stmt->bind_param("iii", $emailNotifications, $smsNotifications, $userId);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Preferencje powiadomień zostały zaktualizowane.";
    } else {
        $_SESSION['error_message'] = "Wystąpił błąd podczas zapisywania preferencji.";
    }
    header("Location: /public/user/dashboard.php#notification-settings");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Użytkownika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/theme.css">
</head>
<body>
    <?php include '../../views/shared/navbar_user.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar: Menu z lewej -->
            <nav class="col-12 col-md-3 col-xl-2 bg-dark sidebar p-0">
                <ul class="nav flex-column text-white">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#bookings">Rezerwacje</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#profile">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#personal-data">Zmień Dane Osobowe</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#reset-password">Zresetuj Hasło</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#documents">Twoje Dokumenty</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#notification-settings">Ustawienia Powiadomień</a>
                    </li>
                </ul>
            </nav>

            <!-- Główna treść: ładowanie plików sekcji -->
            <main class="col-12 col-md-9 col-xl-10 py-3">
                <div id="bookings">
                    <h1 class="text-center">Twoje Rezerwacje</h1>
                    <p class="text-center">Zarządzaj swoimi rezerwacjami poniżej.</p>

                    <?php if ($bookings->num_rows > 0): ?>
                        <table class="table table-bordered mt-4">
                            <thead>
                                <tr>
                                    <th>ID Rezerwacji</th>
                                    <th>Pojazd</th>
                                    <th>Numer Rejestracyjny</th>
                                    <th>Data Odbioru</th>
                                    <th>Data Zwrotu</th>
                                    <th>Cena Całkowita</th>
                                    <th>Status</th>
                                    <th>Umowa</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $booking['id']; ?></td>
                                        <td><?php echo "{$booking['make']} {$booking['model']}"; ?></td>
                                        <td><?php echo $booking['registration_number']; ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($booking['pickup_date'])); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($booking['dropoff_date'])); ?></td>
                                        <td><?php echo number_format($booking['total_price'], 2, ',', ' '); ?> PLN</td>
                                        <td><?php echo ucfirst($booking['status']); ?></td>
                                        <td>
                                            <?php if ($booking['rental_contract_pdf']): ?>
                                                <a href="<?php echo $booking['rental_contract_pdf']; ?>" target="_blank">Pobierz</a>
                                            <?php else: ?>
                                                Niedostępna
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($booking['status'] === 'active'): ?>
                                                <a href="/controllers/booking_controller.php?action=cancel&id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Czy na pewno chcesz anulować tę rezerwację?');">Anuluj</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info text-center mt-4">
                            Nie masz żadnych aktywnych rezerwacji.
                        </div>
                    <?php endif; ?>
                </div>

                <div id="profile">
                    <h2 class="mt-5">Twój Profil</h2>
                    <div class="card p-4">
                        <h2>Dane Osobowe</h2>
                        <p><strong>Imię:</strong> <span><?php echo $userDetails['name']; ?></span></p>
                        <p><strong>Nazwisko:</strong> <span><?php echo $userDetails['surname']; ?></span></p>
                        <p><strong>E-mail:</strong> <span><?php echo $userDetails['email']; ?></span></p>
                        <p><strong>Adres:</strong> <span><?php echo $userDetails['address']; ?></span></p>
                        <p><strong>PESEL lub Numer Dowodu:</strong> <span><?php echo $userDetails['pesel_or_id']; ?></span></p>
                        <a href="#personal-data" class="btn btn-primary mt-3">Zmień Dane Osobowe</a>
                    </div>
                </div>

                <div id="personal-data">
                    <h2 class="mt-5">Zmień Dane Osobowe</h2>
                    <form action="/user/user_controller_proxy.php" method="POST" class="standard-form">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label for="name" class="form-label">Imię</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo $userDetails['name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="surname" class="form-label">Nazwisko</label>
                            <input type="text" id="surname" name="surname" class="form-control" value="<?php echo $userDetails['surname']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo $userDetails['email']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="address_part1" class="form-label">Adres - Część 1</label>
                            <input type="text" id="address_part1" name="address_part1" class="form-control" value="<?php echo explode(' ', $userDetails['address'])[0]; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="address_part2" class="form-label">Adres - Część 2</label>
                            <input type="text" id="address_part2" name="address_part2" class="form-control" value="<?php echo implode(' ', array_slice(explode(' ', $userDetails['address']), 1)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="pesel_or_id" class="form-label">PESEL lub Numer Dowodu</label>
                            <input type="text" id="pesel_or_id" name="pesel_or_id" class="form-control" value="<?php echo $userDetails['pesel_or_id']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefon</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo $userDetails['phone']; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Zapisz Zmiany</button>
                    </form>
                </div>

                <div id="reset-password">
                    <h2 class="mt-5">Zresetuj Hasło</h2>
                    <form action="/user/user_controller_proxy.php" method="POST" class="standard-form">
                        <input type="hidden" name="action" value="reset_password">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Obecne Hasło</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nowe Hasło</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Potwierdź Nowe Hasło</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Zresetuj Hasło</button>
                    </form>
                </div>

                <div id="documents">
                    <h2 class="mt-5">Twoje Dokumenty</h2>
                    <?php if (!empty($userDocuments)): ?>
                        <ul class="list-group">
                            <?php foreach ($userDocuments as $document): ?>
                                <li class="list-group-item">
                                    <a href="<?php echo $document; ?>" target="_blank"><?php echo basename($document); ?></a>
                                    <div id="pdf-viewer-<?php echo basename($document, ".pdf"); ?>" class="pdf-viewer"></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-info">Brak dokumentów do wyświetlenia.</div>
                    <?php endif; ?>
                </div>
                <div id="notification-settings">
                    <h2 class="mt-5">Ustawienia Powiadomień</h2>
                    <form method="POST" action="/public/user/notification_settings_proxy.php" class="standard-form">
                        <div class="form-check mb-3">
                            <input type="checkbox" id="email_notifications" name="email_notifications" class="form-check-input" 
                                <?php echo $preferences['email_notifications'] ? 'checked' : ''; ?>>
                            <label for="email_notifications" class="form-check-label">Otrzymuj powiadomienia e-mail</label>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" id="sms_notifications" name="sms_notifications" class="form-check-input" 
                                <?php echo $preferences['sms_notifications'] ? 'checked' : ''; ?>>
                            <label for="sms_notifications" class="form-check-label">Otrzymuj powiadomienia SMS</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Zapisz</button>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5.3.0 bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
