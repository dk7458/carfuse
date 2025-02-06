/*
|--------------------------------------------------------------------------
| Raporty Użytkownika
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi generowanie raportów dotyczących rezerwacji,
| płatności i aktywności w systemie. Raporty mogą być eksportowane do CSV, PDF, JSON.
|
| Ścieżka: App/Views/user/reports.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, dynamiczne generowanie raportów)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane do raportów)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego generowania raportów)
| - Chart.js (wizualizacja raportów)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Raporty</h1>

<div class="user-reports-container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-body">
                    <h4>Generowanie Raportów</h4>
                    <form id="reportForm">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="reportType" class="form-label">Typ raportu</label>
                            <select class="form-select" id="reportType" name="reportType" required>
                                <option value="" disabled selected>Wybierz typ raportu</option>
                                <option value="bookings">Rezerwacje</option>
                                <option value="payments">Płatności</option>
                                <option value="activity">Aktywność użytkownika</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="startDate" class="form-label">Data początkowa</label>
                                <input type="date" class="form-control" id="startDate" name="startDate" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="endDate" class="form-label">Data końcowa</label>
                                <input type="date" class="form-control" id="endDate" name="endDate" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="format" class="form-label">Format raportu</label>
                            <select class="form-select" id="format" name="format" required>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Generuj raport</button>
                    </form>

                    <div id="responseMessage" class="alert mt-3" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h4>Podgląd raportu</h4>
        <canvas id="reportChart"></canvas>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const reportForm = document.getElementById("reportForm");
    const responseMessage = document.getElementById("responseMessage");

    reportForm.addEventListener("submit", function(e) {
        e.preventDefault();
        generateReport(new FormData(reportForm));
    });

    function generateReport(formData) {
        let url = "/api/user/reports.php";

        fetch(url, {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            responseMessage.style.display = "block";
            if (data.success) {
                responseMessage.className = "alert alert-success";
                responseMessage.textContent = "Raport wygenerowany pomyślnie! Pobierz go tutaj: " + data.download_link;
                renderChart(data.chartData);
            } else {
                responseMessage.className = "alert alert-danger";
                responseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            responseMessage.style.display = "block";
            responseMessage.className = "alert alert-danger";
            responseMessage.textContent = "Błąd połączenia z serwerem.";
            console.error("Błąd generowania raportu:", error);
        });
    }

    function renderChart(chartData) {
        const ctx = document.getElementById("reportChart").getContext("2d");
        new Chart(ctx, {
            type: "bar",
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: "Liczba zgłoszeń",
                    data: chartData.values,
                    backgroundColor: "rgba(54, 162, 235, 0.6)"
                }]
            }
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
