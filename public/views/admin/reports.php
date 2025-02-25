<?php
require_once __DIR__ . '/../../../helpers/SecurityHelper.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>

/*
|--------------------------------------------------------------------------
| Raporty Administratora
|--------------------------------------------------------------------------
| Ten plik umożliwia generowanie raportów dotyczących rezerwacji, płatności,
| użytkowników, aktywności w systemie oraz audytów.
|
| Ścieżka: App/Views/admin/reports.php
|
| Zależy od:
| - JavaScript: /js/admin.js (obsługa generowania raportów, AJAX)
| - CSS: /css/admin.css (stylizacja formularzy i tabeli)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane pobierane z bazy)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX, dynamiczne generowanie raportów)
| - Chart.js (wizualizacja raportów)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Raporty Systemowe</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Generowanie raportów</h3>
        <button class="btn btn-secondary" id="clearFilters">Reset</button>
    </div>

    <!-- Formularz generowania raportów -->
    <form id="adminReportForm" class="mt-4">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="reportType" class="form-label">Typ raportu</label>
            <select class="form-select" id="reportType" name="reportType" required>
                <option value="" disabled selected>Wybierz typ raportu</option>
                <option value="bookings">Rezerwacje</option>
                <option value="payments">Płatności</option>
                <option value="users">Użytkownicy</option>
                <option value="activity">Aktywność użytkowników</option>
                <option value="audit">Logi audytowe</option>
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

    <div class="mt-4">
        <h4>Podgląd raportu</h4>
        <canvas id="reportChart"></canvas>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const reportForm = document.getElementById("adminReportForm");
    const responseMessage = document.getElementById("responseMessage");
    const clearFilters = document.getElementById("clearFilters");

    reportForm.addEventListener("submit", function(e) {
        e.preventDefault();
        generateReport(new FormData(reportForm));
    });

    clearFilters.addEventListener("click", function() {
        reportForm.reset();
        responseMessage.style.display = "none";
    });

    function generateReport(formData) {
        let url = "/api/admin/reports.php";

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

<?php
// Date range validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_date'], $_POST['end_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if (strtotime($start_date) > strtotime($end_date)) {
        echo 'Invalid date range';
        exit();
    }

    // Export reports logic (PDF, CSV)
    // ...existing code...
}
?>
