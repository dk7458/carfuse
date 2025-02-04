<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-center">Metody płatności</h1>

<div class="payment-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Dodaj nową metodę płatności</h3>
        <form id="addMethodForm">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="method_name" class="form-label">Nazwa metody</label>
                <input type="text" id="method_name" name="method_name" class="form-control" placeholder="Wprowadź nazwę metody" required>
            </div>

            <div class="mb-3">
                <label for="details" class="form-label">Szczegóły</label>
                <textarea id="details" name="details" class="form-control" rows="2" placeholder="Podaj szczegóły metody" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">Dodaj metodę płatności</button>
        </form>

        <h4 class="mt-5">Istniejące metody płatności</h4>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nazwa</th>
                    <th>Szczegóły</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody id="paymentMethods">
                <!-- Lista metod płatności ładowana dynamicznie -->
            </tbody>
        </table>
    </div>
</div>

<script src="/js/payments.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
