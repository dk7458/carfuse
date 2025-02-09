/*
|--------------------------------------------------------------------------
| Historia Płatności
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi przeglądanie historii płatności i ich statusów.
| Obsługuje filtrowanie oraz podgląd szczegółów transakcji.
|
| Ścieżka: App/Views/payments/history.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, dynamiczne pobieranie płatności)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane płatności)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego pobierania płatności)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Historia Płatności</h1>

<div class="payments-history-container">
    <!-- Filtry płatności -->
    <form id="paymentFilterForm" class="row mt-4">
        <?= csrf_field() ?>
        <div class="col-md-4">
            <select class="form-control" name="status">
                <option value="">Wybierz status</option>
                <option value="completed">Zakończona</option>
                <option value="pending">Oczekująca</option>
                <option value="failed">Nieudana</option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="date" class="form-control" name="start_date" placeholder="Data początkowa">
        </div>
        <div class="col-md-4 text-end">
            <button type="submit" class="btn btn-primary">Filtruj</button>
        </div>
    </form>

    <!-- Tabela płatności -->
    <table class="table table-bordered table-striped table-responsive mt-3">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Kwota</th>
                <th>Metoda</th>
                <th>Status</th>
                <th>Data</th>
                <th class="text-center">Akcje</th>
            </tr>
        </thead>
        <tbody id="paymentList">
            <!-- Dane będą ładowane dynamicznie -->
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterForm = document.getElementById("paymentFilterForm");

    filterForm.addEventListener("submit", function(e) {
        e.preventDefault();
        fetchPayments(new FormData(filterForm));
    });

    function fetchPayments(formData = null) {
        let url = "/api/user/payments.php";
        if (formData) {
            url += "?" + new URLSearchParams(formData).toString();
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const paymentTable = document.getElementById("paymentList");
                paymentTable.innerHTML = "";

                if (data.length === 0) {
                    paymentTable.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Brak płatności spełniających kryteria.</td></tr>`;
                } else {
                    data.forEach(payment => {
                        paymentTable.innerHTML += `
                            <tr>
                                <td>${payment.id}</td>
                                <td>${payment.amount} PLN</td>
                                <td>${payment.method}</td>
                                <td>${payment.status}</td>
                                <td>${payment.date}</td>
                                <td class="text-center">
                                    <button class="btn btn-info btn-sm" onclick="viewPayment(${payment.id})">Podgląd</button>
                                    <button class="btn btn-warning btn-sm" onclick="refundPayment(${payment.id})">Zwrot</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd pobierania płatności:", error));
    }

    function viewPayment(paymentId) {
        fetch(`/api/user/payment_details.php?id=${paymentId}`)
            .then(response => response.json())
            .then(data => {
                alert(`Szczegóły płatności:\n\nKwota: ${data.amount} PLN\nMetoda: ${data.method}\nStatus: ${data.status}\nData: ${data.date}`);
            })
            .catch(error => console.error("Błąd pobierania szczegółów płatności:", error));
    }

    function refundPayment(paymentId) {
        if (confirm("Czy na pewno chcesz zwrócić tę płatność?")) {
            fetch(`/api/user/refund_payment.php?id=${paymentId}`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Płatność została zwrócona.");
                        fetchPayments();
                    } else {
                        alert("Błąd zwrotu płatności: " + data.message);
                    }
                })
                .catch(error => console.error("Błąd zwrotu płatności:", error));
        }
    }

    fetchPayments();
});
</script>
