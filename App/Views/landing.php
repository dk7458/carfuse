<?php require_once __DIR__ . '/layouts/header.php'; ?>

<section class="hero">
    <div class="container text-center">
        <h1>Znajdź idealne auto na swoją podróż</h1>
        <p>Elastyczny wynajem, najlepsze ceny i wsparcie 24/7.</p>
        <form class="search-form" action="/search" method="GET">
            <input type="text" name="location" placeholder="Wpisz lokalizację odbioru" required>
            <input type="date" name="pickup_date" required>
            <input type="date" name="return_date" required>
            <button type="submit" class="btn btn-primary">Szukaj aut</button>
        </form>
    </div>
</section>

<section class="features text-center">
    <h2>Dlaczego warto wybrać CarFuse?</h2>
    <div class="row justify-content-center">
        <div class="col-md-3 feature-box">
            <h4>🚗 Duży wybór pojazdów</h4>
            <p>Wybierz spośród szerokiej gamy samochodów.</p>
        </div>
        <div class="col-md-3 feature-box">
            <h4>💰 Najlepsze ceny</h4>
            <p>Zawsze konkurencyjne ceny i atrakcyjne promocje.</p>
        </div>
        <div class="col-md-3 feature-box">
            <h4>📞 Wsparcie 24/7</h4>
            <p>Nasz zespół jest dostępny przez całą dobę.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
