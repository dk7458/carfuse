<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Realizacja płatności</h1>

<div class="payment-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Wprowadź dane płatności</h3>
        <form id="paymentForm">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="user_id" class="form-label">ID użytkownika</label>
                <input type="number" id="user_id" name="user_id" class="form-control" placeholder="Wprowadź ID użytkownika" required>
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">Kwota</label>
                <input type="number" id="amount" name="amount" class="form-control" placeholder="Podaj kwotę" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="payment_method_id" class="form-label">Metoda płatności</label>
                <select id="payment_method_id" name="payment_method_id" class="form-select" required>
                    <option value="" disabled selected>Wybierz metodę płatności...</option>
                    <option value="1">Karta kredytowa</option>
                    <option value="2">PayPal</option>
                    <option value="3">Przelew bankowy</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Dokonaj płatności</button>
        </form>

        <div id="responseMessage" class="mt-3"></div>
    </div>
</div>

<script src="/js/payments.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
