<?php
/*
|--------------------------------------------------------------------------
| Strona Główna CarFuse
|--------------------------------------------------------------------------
| Ten plik zawiera główną stronę serwisu CarFuse. 
| Zawiera sekcję wyszukiwania pojazdów i kluczowe cechy platformy.
|
| Ścieżka: App/Views/landing.php
|
| Technologie:
| - PHP 8+ (backend)
| - HTML, CSS (interfejs)
| - JavaScript (dynamiczna walidacja formularza)
*/

define('BASE_PATH', '/home/u122931475/domains/carfuse.pl/public_html'); // Set absolute path

require_once BASE_PATH . '/bootstrap.php'; // Ensure the bootstrap file is included
require_once BASE_PATH . '/routes/web.php'; // Load FastRoute routes
require_once BASE_PATH . '/App/Helpers/SecurityHelper.php'; // Load security helpers
require_once BASE_PATH . '/App/Services/NotificationService.php'; // Load notification services
require_once BASE_PATH . '/App/Services/Validator.php'; // Load validation services

// Debugging output for FastRoute
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dispatcher = require BASE_PATH . '/config/routes.php';

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(["error" => "404 Not Found - Route Not Found"]);
        exit;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(["error" => "405 Method Not Allowed"]);
        exit;

    case FastRoute\Dispatcher::FOUND:
        [$controller, $method] = $routeInfo[1];
        $vars = $routeInfo[2];

        if (!class_exists($controller) || !method_exists($controller, $method)) {
            http_response_code(500);
            echo json_encode(["error" => "500 Internal Server Error - Invalid Route Handler"]);
            exit;
        }

        $controllerInstance = new $controller();
        call_user_func_array([$controllerInstance, $method], $vars);
        exit;
}

require_once __DIR__ . '/layouts/header.php';
?>

<style>
    /* Main CSS integrated directly */
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        color: #333;
        margin: 0;
        padding: 0;
    }
    button, input[type="submit"] {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
    }
    button:hover, input[type="submit"]:hover {
        background-color: #0056b3;
    }
    button:focus, input[type="submit"]:focus {
        outline: 2px solid #ffcc00;
    }
    .hero, .features, .cta {
        text-align: center;
        padding: 20px;
    }
    .feature-box {
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 5px;
        margin: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .cta {
        background-color: #28a745;
        color: white;
        padding: 20px;
        border-radius: 5px;
    }
    @media (max-width: 768px) {
        body {
            font-size: 14px;
        }
    }
</style>

<script src="/js/landing.js" defer></script>

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

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
