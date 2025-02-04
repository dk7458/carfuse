<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-center">Zwroty płatności</h1>

<div class="payment-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Zleć zwrot płatności</h3>
        <form id="refundForm">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="transaction_id" class="form-label">ID transakcji</label>
                <input type="number" id="transaction_id" name="transaction_id" class="form-control" placeholder="Wprowadź ID transakcji" required>
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">Kwota zwrotu</label>
                <input type="number" id="amount" name="amount" class="form-control" placeholder="Podaj kwotę" step="0.01" required>
            </div>

            <button type="submit" class="btn btn-danger w-100">Zatwierdź zwrot</button>
        </form>

        <div id="responseMessage" class="mt-3"></div>
    </div>
</div>

<script src="/js/payments.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
