<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php'; // Bootstrap application
require_once __DIR__ . '/../vendor/autoload.php'; // Load dependencies

header("Content-Type: text/html; charset=UTF-8");

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarFuse</title>
    <link rel="stylesheet" href="/public/css/landing.css">
    <script src="/public/js/landing.js" defer></script>
</head>
<body>

<section class="hero">
    <div class="container text-center">
        <h1>🚗 Znajdź idealne auto na swoją podróż</h1>
        <p>Elastyczny wynajem, najlepsze ceny i wsparcie 24/7.</p>

        <form class="search-form" action="/search" method="GET" onsubmit="return validateDates();">
            <input type="text" name="location" placeholder="Wpisz lokalizację odbioru" required>
            <input type="date" id="pickup_date" name="pickup_date" required>
            <input type="date" id="return_date" name="return_date" required>
            <button type="submit" class="btn btn-primary">Szukaj aut</button>
        </form>

        <p id="dateError" class="text-danger mt-2" style="display:none;">Data zwrotu nie może być wcześniejsza niż odbioru.</p>
    </div>
</section>

<section class="features text-center">
    <h2>Dlaczego warto wybrać CarFuse?</h2>
    <div class="row justify-content-center">
        <div class="col-md-3 feature-box">
            <h4>🚗 Szeroki wybór pojazdów</h4>
            <p>Wybierz spośród różnych kategorii aut – od ekonomicznych po luksusowe.</p>
        </div>
        <div class="col-md-3 feature-box">
            <h4>💰 Najlepsze ceny</h4>
            <p>Zawsze konkurencyjne ceny i wyjątkowe promocje dla stałych klientów.</p>
        </div>
        <div class="col-md-3 feature-box">
            <h4>📞 Wsparcie 24/7</h4>
            <p>Nasz zespół jest dostępny całą dobę, aby pomóc Ci w każdej sytuacji.</p>
        </div>
    </div>
</section>

<section class="cta text-center">
    <h2>Zarezerwuj swoje auto już dziś!</h2>
    <p>Najlepsze oferty dostępne w kilku kliknięciach.</p>
    <a href="/search" class="btn btn-success btn-lg">Sprawdź dostępność</a>
</section>

</body>
</html>
