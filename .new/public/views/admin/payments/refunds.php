<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>

/*
|--------------------------------------------------------------------------
| Panel Zwrotów Płatności
|--------------------------------------------------------------------------
| Ten plik umożliwia administratorowi przegląd i zarządzanie zwrotami.
| Administrator może sprawdzić status zwrotów oraz anulować wybrane operacje.
|
| Ścieżka: App/Views/admin/payments/refunds.php
|
| Zależy od:
| - JavaScript: /js/admin.js (obsługa AJAX, filtrowanie, anulowanie zwrotów)
| - CSS: /css/admin.css (stylizacja interfejsu)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane zwrotów)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do pobierania danych)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Zwroty Płatności</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Historia Zwrotów</h3>
    </div>

    <!-- Filtry zwrotów -->
    <form id="refundFilterForm" class="row mt-4">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <input type="text" class="form-control" name="user_id" placeholder="ID Użytkownika">
        </div>
        <div class="col-md-3">
            <select class="form-control" name="status">
                <option value="">Status zwrotu</option>
                <option value="pending">Oczekujący</option>
                <option value="completed">Zakończony</option>
                <option value="failed">Nieudany</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" name="start_date" placeholder="Data początkowa">
        </div>
        <div class="col-md-3 text-end">
            <button type="submit" class="btn btn-primary">Filtruj</button>
        </div>
    </form>

    <!-- Tabela zwrotów -->
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Użytkownik</th>
                <th>Kwota</th>
                <th>Status</th>
                <th>Data</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody id="refundList">
            <!-- Dane będą ładowane dynamicznie -->
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterForm = document.getElementById("refundFilterForm");

    filterForm.addEventListener("submit", function(e) {
        e.preventDefault();
        fetchRefunds(new FormData(filterForm));
    });

    function fetchRefunds(formData = null) {
        let url = "/api/admin/refunds.php";
        if (formData) {
            url += "?" + new URLSearchParams(formData).toString();
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const refundTable = document.getElementById("refundList");
                refundTable.innerHTML = "";

                if (data.length === 0) {
                    refundTable.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Brak zwrotów spełniających kryteria.</td></tr>`;
                } else {
                    data.forEach(refund => {
                        refundTable.innerHTML += `
                            <tr>
                                <td>${refund.id}</td>
                                <td>${refund.user}</td>
                                <td>${refund.amount} PLN</td>
                                <td>${refund.status}</td>
                                <td>${refund.date}</td>
                                <td>
                                    ${refund.status === "pending" ? `<button class="btn btn-danger btn-sm" onclick="cancelRefund(${refund.id})">Anuluj</button>` : ""}
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd pobierania zwrotów:", error));
    }

    function cancelRefund(refundId) {
        if (!confirm("Czy na pewno chcesz anulować ten zwrot?")) return;

        fetch(`/api/admin/cancel_refund.php?id=${refundId}`, { method: "POST" })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Zwrot został anulowany.");
                    fetchRefunds();
                } else {
                    alert("Błąd anulowania zwrotu: " + data.error);
                }
            })
            .catch(error => console.error("Błąd anulowania zwrotu:", error));
    }

    fetchRefunds();
});
</script>
