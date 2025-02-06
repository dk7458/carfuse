/*
|--------------------------------------------------------------------------
| Panel Transakcji Płatności
|--------------------------------------------------------------------------
| Ten plik umożliwia administratorowi przegląd i zarządzanie wszystkimi
| transakcjami finansowymi systemu. Obsługuje filtrowanie i eksport danych.
|
| Ścieżka: App/Views/admin/payments/transactions.php
|
| Zależy od:
| - JavaScript: /js/admin.js (obsługa AJAX, filtrowanie, eksport)
| - CSS: /css/admin.css (stylizacja interfejsu)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane transakcji)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do pobierania danych)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Transakcje Płatności</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Historia Transakcji</h3>
        <div>
            <button class="btn btn-success btn-sm" id="exportCSV">Eksport CSV</button>
            <button class="btn btn-danger btn-sm" id="exportPDF">Eksport PDF</button>
        </div>
    </div>

    <!-- Filtry transakcji -->
    <form id="transactionFilterForm" class="row mt-4">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <input type="text" class="form-control" name="user_id" placeholder="ID Użytkownika">
        </div>
        <div class="col-md-3">
            <select class="form-control" name="status">
                <option value="">Status transakcji</option>
                <option value="completed">Zakończona</option>
                <option value="pending">Oczekująca</option>
                <option value="failed">Nieudana</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-control" name="method">
                <option value="">Metoda płatności</option>
                <option value="card">Karta kredytowa</option>
                <option value="paypal">PayPal</option>
                <option value="transfer">Przelew bankowy</option>
            </select>
        </div>
        <div class="col-md-3 text-end">
            <button type="submit" class="btn btn-primary">Filtruj</button>
        </div>
    </form>

    <!-- Tabela transakcji -->
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Użytkownik</th>
                <th>Kwota</th>
                <th>Metoda</th>
                <th>Status</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody id="transactionList">
            <!-- Dane będą ładowane dynamicznie -->
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterForm = document.getElementById("transactionFilterForm");
    const exportCSV = document.getElementById("exportCSV");
    const exportPDF = document.getElementById("exportPDF");

    filterForm.addEventListener("submit", function(e) {
        e.preventDefault();
        fetchTransactions(new FormData(filterForm));
    });

    exportCSV.addEventListener("click", function() {
        window.location.href = "/api/admin/export_transactions.php?format=csv";
    });

    exportPDF.addEventListener("click", function() {
        window.location.href = "/api/admin/export_transactions.php?format=pdf";
    });

    function fetchTransactions(formData = null) {
        let url = "/api/admin/transactions.php";
        if (formData) {
            url += "?" + new URLSearchParams(formData).toString();
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const transactionTable = document.getElementById("transactionList");
                transactionTable.innerHTML = "";

                if (data.length === 0) {
                    transactionTable.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Brak transakcji spełniających kryteria.</td></tr>`;
                } else {
                    data.forEach(transaction => {
                        transactionTable.innerHTML += `
                            <tr>
                                <td>${transaction.id}</td>
                                <td>${transaction.user}</td>
                                <td>${transaction.amount} PLN</td>
                                <td>${transaction.method}</td>
                                <td>${transaction.status}</td>
                                <td>${transaction.date}</td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd pobierania transakcji:", error));
    }

    fetchTransactions();
});
</script>
