<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../App/Helpers/SecurityHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logFile = __DIR__ . '/../../logs/debug.log';

// Log the inclusion of the home view
file_put_contents($logFile, "[VIEW] Including home.php" . PHP_EOL, FILE_APPEND);
?>
<?php $page = 'home'; ?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarFuse - Strona Główna</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include __DIR__ . '/layouts/navbar.php'; ?>

<section class="hero-section">
    <div class="container text-center">
        <h1>Witaj w CarFuse!</h1>
        <p>Najlepszy sposób na wynajem i zarządzanie pojazdami.</p>
        <a href="/dashboard" class="btn btn-primary">Panel</a>
        <a href="#features-section" class="btn btn-secondary" id="register-btn">Zaloguj się</a>
        <a href="/profile" class="btn btn-tertiary">Profil</a>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <h2>Dlaczego CarFuse?</h2>
        <div class="row">
            <div class="col-md-4 text-center">
                <i class="icon fas fa-car"></i>
                <h3>Łatwy Wynajem</h3>
                <p>Wynajmuj pojazdy szybko i wygodnie online.</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="icon fas fa-lock"></i>
                <h3>Bezpieczeństwo</h3>
                <p>Wszystkie rezerwacje są weryfikowane i zabezpieczone.</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="icon fas fa-wallet"></i>
                <h3>Elastyczne Płatności</h3>
                <p>Obsługujemy płatności kartą, PayPal i inne metody.</p>
            </div>
        </div>
    </div>
</section>

<section class="testimonials-section">
    <div class="container">
        <h2>Opinie Klientów</h2>
        <div class="row">
            <div class="col-md-6">
                <blockquote>
                    <p>"Najlepsza platforma do wynajmu aut! Proces jest szybki i bezproblemowy."</p>
                    <cite>- Jan Kowalski</cite>
                </blockquote>
            </div>
            <div class="col-md-6">
                <blockquote>
                    <p>"Wspaniała obsługa klienta i bardzo dobre ceny. Polecam!"</p>
                    <cite>- Anna Nowak</cite>
                </blockquote>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/layouts/footer.php'; ?>
<script src="/js/shared.js"></script>
</body>
</html>
