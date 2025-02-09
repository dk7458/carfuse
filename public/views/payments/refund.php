<?php
/*
|--------------------------------------------------------------------------
| Historia Zwrotów Płatności
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi przeglądanie historii swoich zwrotów płatności
| oraz ich statusów. Obsługuje filtrowanie oraz podgląd szczegółów.
|
| Ścieżka: App/Views/payments/refund.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, dynamiczne pobieranie zwrotów)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane zwrotów płatności)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego pobierania zwrotów)
| - HTML, CSS (interfejs)
*/


if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
?>

<h1 class="text-center">Historia Zwrotów</h1>

<div class="payments-refund-container">
    <!-- Filtry zwrotów -->
    <form id="refundFilterForm" class="row mt-4">
        <?= csrf_field() ?>
        <div class="col-md-4">
            <select class="form-control" name="status">
                <option value="">Wybierz status</option>
                <option value="processed">Zakończony</option>
                <option value="pending">Oczekujący</option>
                <option value="failed">Nieudany</option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="date" class="form-control" name="start_date" placeholder="Data początkowa">
        </div>
        <div class="col-md-4 text-end">
            <button type="submit" class="btn btn-primary">Filtruj</button>
        </div>
    </form>

    <!-- Tabela zwrotów -->
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Kwota</th>
                <th>Metoda</th>
                <th>Status</th>
                <th>Data</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody id="refundList">
            <tr>
                <td colspan="6" class="text-center text-muted">Ładowanie danych...</td>
            </tr>
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
        let url = "/api/user/refunds.php";
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
                        let statusColor;
                        switch (refund.status) {
                            case "processed": statusColor = "text-success"; break;
                            case "pending": statusColor = "text-warning"; break;
                            case "failed": statusColor = "text-danger"; break;
                            default: statusColor = "text-secondary";
                        }

                        refundTable.innerHTML += `
                            <tr>
                                <td>${refund.id}</td>
                                <td>${refund.amount} PLN</td>
                                <td>${refund.method}</td>
                                <td class="${statusColor}">${refund.status}</td>
                                <td>${refund.date}</td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="viewRefund(${refund.id})">Podgląd</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd pobierania zwrotów:", error));
    }

    function viewRefund(refundId) {
        fetch(`/api/user/refund_details.php?id=${refundId}`)
            .then(response => response.json())
            .then(data => {
                alert(`Szczegóły zwrotu:\n\nKwota: ${data.amount} PLN\nMetoda: ${data.method}\nStatus: ${data.status}\nData: ${data.date}`);
            })
            .catch(error => console.error("Błąd pobierania szczegółów zwrotu:", error));
    }

    fetchRefunds();
});
</script>
