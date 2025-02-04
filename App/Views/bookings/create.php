<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Nowa rezerwacja</h1>

<div class="booking-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Wybierz szczegóły rezerwacji</h3>
        <form id="bookingForm">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="vehicle" class="form-label">Wybierz pojazd</label>
                <select id="vehicle" name="vehicle" class="form-select" required>
                    <option value="" disabled selected>Wybierz pojazd...</option>
                    <!-- Lista pojazdów ładowana dynamicznie -->
                </select>
            </div>

            <div class="mb-3">
                <label for="pickup_date" class="form-label">Data odbioru</label>
                <input type="date" id="pickup_date" name="pickup_date" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="return_date" class="form-label">Data zwrotu</label>
                <input type="date" id="return_date" name="return_date" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="payment_method" class="form-label">Metoda płatności</label>
                <select id="payment_method" name="payment_method" class="form-select" required>
                    <option value="" disabled selected>Wybierz metodę płatności...</option>
                    <option value="card">Karta kredytowa</option>
                    <option value="paypal">PayPal</option>
                    <option value="transfer">Przelew bankowy</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Zarezerwuj</button>
        </form>
    </div>
</div>

<script src="/js/bookings.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
