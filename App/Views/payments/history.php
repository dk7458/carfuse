<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Historia transakcji</h1>

<div class="payment-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Lista transakcji</h3>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID transakcji</th>
                    <th>ID użytkownika</th>
                    <th>ID rezerwacji</th>
                    <th>Kwota</th>
                    <th>Typ</th>
                    <th>Status</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody id="transactionHistory">
                <!-- Dane transakcji ładowane dynamicznie -->
            </tbody>
        </table>
    </div>
</div>

<script src="/js/payments.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
