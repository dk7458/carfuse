<?php

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

$userId = $_SESSION['user_id'];

// Fetch user details
$userDetails = $conn->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Użytkownika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">

</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Twój Profil</h1>
        <div class="card p-4">
            <h2>Dane Osobowe</h2>
            <p><strong>Imię:</strong> <?php echo $userDetails['name']; ?></p>
            <p><strong>Nazwisko:</strong> <?php echo $userDetails['surname']; ?></p>
            <p><strong>E-mail:</strong> <?php echo $userDetails['email']; ?></p>
            <p><strong>Adres:</strong> <?php echo $userDetails['address']; ?></p>
            <p><strong>PESEL lub Numer Dowodu:</strong> <?php echo $userDetails['pesel_or_id']; ?></p>
        </div>
    </div>
</body>
</html>
