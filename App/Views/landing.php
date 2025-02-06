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
| Zależy od:
| - JavaScript: /js/landing.js (obsługa dynamicznej walidacji formularza)
| - CSS: /css/landing.css (stylizacja strony głównej)
|
| Technologie:
| - PHP 8+ (backend)
| - HTML, CSS (interfejs)
| - JavaScript (dynamiczna walidacja formularza)
*/

require_once __DIR__ . '/layouts/header.php';
?>

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

<script>
function validateDates() {
    const pickupDate = document.getElementById("pickup_date").value;
    const returnDate = document.getElementById("return_date").value;
    const dateError = document.getElementById("dateError");

    if (new Date(returnDate) < new Date(pickupDate)) {
        dateError.style.display = "block";
        return false;
    } else {
        dateError.style.display = "none";
        return true;
    }
}
</script>
