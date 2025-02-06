<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php'; // Bootstrap application
require_once __DIR__ . '/../vendor/autoload.php'; // Load dependencies
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php'; // Load security functions globally

header("Content-Type: text/html; charset=UTF-8");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfuse - Wynajmij auto szybko i łatwo</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <script src="/public/js/main.js" defer></script>
</head>
<body>

<?php include __DIR__ . '/layouts/header.php'; ?>

<section class="hero">
    <h1>Znajdź idealne auto na swoją podróż</h1>
    <p>Elastyczny wynajem, najlepsze ceny i wsparcie 24/7.</p>
    <form class="search-form" action="/search" method="GET" onsubmit="return validateDates();">
        <input type="text" name="location" placeholder="Wpisz lokalizację odbioru" required>
        <input type="date" id="pickup_date" name="pickup_date" required>
        <input type="date" id="return_date" name="return_date" required>
        <button type="submit">Szukaj aut</button>
    </form>
    <p id="dateError" class="error-message">❌ Data zwrotu nie może być wcześniejsza niż odbioru.</p>
</section>

<section class="features">
    <h2>Dlaczego warto wybrać Carfuse?</h2>
    <div class="feature-list">
        <div class="feature">✔ Gwarancja najlepszych cen</div>
        <div class="feature">✔ Wsparcie klienta 24/7</div>
        <div class="feature">✔ Elastyczne warunki wynajmu</div>
    </div>
</section>

<?php include __DIR__ . '/layouts/footer.php'; ?>

</body>
</html>
