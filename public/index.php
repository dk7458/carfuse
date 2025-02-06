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
    <title>CarFuse - Wynajem Samochodów</title>
    <link rel="stylesheet" href="/public/css/landing.css">
    <script src="/public/js/landing.js" defer></script>
</head>
<body>

<section class="hero">
    <div class="container">
        <h1 class="hero-title">🚗 Znajdź idealne auto na swoją podróż</h1>
        <p class="hero-subtitle">Elastyczny wynajem, najlepsze ceny i wsparcie 24/7.</p>

        <form class="search-form" action="/search" method="GET" onsubmit="return validateDates();">
            <input type="text" name="location" placeholder="Wpisz lokalizację odbioru" required>
            <input type="date" id="pickup_date" name="pickup_date" required>
            <input type="date" id="return_date" name="return_date" required>
            <button type="submit" class="btn btn-primary">Szukaj aut</button>
        </form>

        <p id="dateError" class="error-message">❌ Data zwrotu nie może być wcześniejsza niż odbioru.</p>
    </div>
</section>

<section class="features">
    <h2>🔥 Dlaczego warto wybrać CarFuse?</h2>
    <div class="features-container">
        <div class="feature-box">
            <h4>🚗 Szeroki wybór pojazdów</h4>
            <p>Od ekonomicznych po luksusowe.</p>
        </div>
        <div class="feature-box">
            <h4>💰 Najlepsze ceny</h4>
            <p>Stałe promocje dla klientów.</p>
        </div>
        <div class="feature-box">
            <h4>📞 Wsparcie 24/7</h4>
            <p>Zawsze do Twojej dyspozycji.</p>
        </div>
    </div>
</section>

<section class="cta">
    <h2>🚀 Zarezerwuj auto już teraz!</h2>
    <a href="/search" class="btn btn-success">Sprawdź dostępność</a>
</section>

</body>
</html>
