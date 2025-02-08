<?php
declare(strict_types=1);
header("Content-Type: text/html; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfuse - Wynajmij auto szybko i łatwo</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <script src="/public/js/shared.js" defer></script>
</head>
<body>

<?php include __DIR__ . '/layouts/header.php'; ?>

<!-- Home Page Content -->
<section class="hero-section">
    <h1>Witaj w CarFuse</h1>
    <p>Wynajmij auto szybko i łatwo!</p>
    <a href="/register" id="register-btn" class="btn btn-primary">Zarejestruj się</a>
</section>

<section class="features">
    <div class="container">
        <div class="row">
            <div class="col-md-4 text-center">
                <img src="/images/icon-fast.png" alt="Szybkie wypożyczenie">
                <h3>Szybka Rezerwacja</h3>
                <p>Rezerwuj samochód w kilka sekund.</p>
            </div>
            <div class="col-md-4 text-center">
                <img src="/images/icon-secure.png" alt="Bezpieczne transakcje">
                <h3>Bezpieczne Transakcje</h3>
                <p>Gwarantujemy w pełni zabezpieczone płatności.</p>
            </div>
            <div class="col-md-4 text-center">
                <img src="/images/icon-selection.png" alt="Szeroki wybór">
                <h3>Szeroki Wybór</h3>
                <p>Wybierz spośród różnych modeli i marek.</p>
            </div>
        </div>
    </div>
</section>

<section class="testimonials">
    <h2 class="text-center">Opinie Klientów</h2>
    <div class="container">
        <div class="testimonial">
            <p>“Najlepsza wypożyczalnia aut, szybko i bezproblemowo!”</p>
            <strong>- Karol K.</strong>
        </div>
        <div class="testimonial">
            <p>“Bezpieczne płatności i świetna obsługa klienta.”</p>
            <strong>- Marta Z.</strong>
        </div>
    </div>
</section>

<?php include __DIR__ . '/layouts/footer.php'; ?>

</body>
</html>
