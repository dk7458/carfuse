<?php
require_once __DIR__ . '/../../../helpers/SecurityHelper.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>

/*
|--------------------------------------------------------------------------
| Panel Zarządzania Płatnościami
|--------------------------------------------------------------------------
| Ten plik umożliwia administratorowi przegląd wszystkich transakcji, zwrotów
| oraz statystyk finansowych. Obsługuje dynamiczne filtrowanie i obsługę zwrotów.
|
| Ścieżka: App/Views/admin/payments/dashboard.php
|
| Zależy od:
| - JavaScript: /js/admin.js (obsługa AJAX, filtrowanie)
| - CSS: /css/admin.css (stylizacja interfejsu)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane transakcji, zwroty)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do pobierania danych)
| - Chart.js (wizualizacja płatności)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Panel Płatności</h1>

<div class="admin-container">
    <div class="row">
        <!-- Statystyki płatności -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Łączna liczba transakcji</h4>
                    <p id="totalTransactions" class="display-6">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Łączna kwota przychodu</h4>
                    <p id="totalRevenue" class="display-6">0 PLN</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Łączna liczba zwrotów</h4>
                    <p id="totalRefunds" class="display-6">0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtry transakcji -->
    <form id="paymentFilterForm" class="row mt-4">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <select class="form-control" name="status">
                <option value="">Wybierz status</option>
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
        <div class="col-md-3">
            <input type="date" class="form-control" name="start_date" placeholder="Data początkowa">
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
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody id="transactionList">
            <!-- Dane będą ładowane dynamicznie -->
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterForm = document.getElementById("paymentFilterForm");

    filterForm.addEventListener("submit", function(e) {
        e.preventDefault();
        fetchTransactions(new FormData(filterForm));
    });

    function fetchTransactions(formData = null) {
        let url = "/api/admin/payments.php";
        if (formData) {
            url += "?" + new URLSearchParams(formData).toString();
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const transactionTable = document.getElementById("transactionList");
                transactionTable.innerHTML = "";

                if (data.length === 0) {
                    transactionTable.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Brak transakcji spełniających kryteria.</td></tr>`;
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
                                <td>
                                    ${transaction.status === "completed" ? `<button class="btn btn-danger btn-sm" onclick="processRefund(${transaction.id})">Zwrot</button>` : ""}
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd pobierania transakcji:", error));
    }

    function processRefund(transactionId) {
        if (!confirm("Czy na pewno chcesz dokonać zwrotu?")) return;

        fetch(`/api/admin/refund.php?id=${transactionId}`, { method: "POST" })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Zwrot został pomyślnie przetworzony.");
                    fetchTransactions();
                } else {
                    alert("Błąd zwrotu: " + data.error);
                }
            })
            .catch(error => console.error("Błąd zwrotu:", error));
    }

    fetchTransactions();
});
</script>
