<?php
require_once __DIR__ . '/../../../helpers/SecurityHelper.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

/*
|--------------------------------------------------------------------------
| Logi Systemowe Administratora
|--------------------------------------------------------------------------
| Ten plik odpowiada za wyświetlanie logów systemowych, które pomagają
| administratorowi diagnozować problemy techniczne.
|
| Ścieżka: App/Views/admin/logs.php
|
| Zależy od:
| - JavaScript: /js/admin.js (obsługa logów, AJAX)
| - CSS: /css/admin.css (stylizacja tabeli)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane pobierane z bazy)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX, dynamiczne ładowanie danych)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Logi Systemowe</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Przegląd Logów Systemowych</h3>
        <button class="btn btn-secondary" id="clearFilters">Wyczyść Filtry</button>
    </div>

    <!-- Filtry logów -->
    <form id="logFilterForm" class="row mb-4">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <select class="form-control" name="log_type">
                <option value="">Wybierz typ logu</option>
                <option value="error">Błąd</option>
                <option value="info">Informacja</option>
                <option value="warning">Ostrzeżenie</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="log_message" placeholder="Wyszukaj w treści logu">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="start_date" placeholder="Data początkowa">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="end_date" placeholder="Data końcowa">
        </div>
        <div class="col-md-2 text-end">
            <button type="submit" class="btn btn-primary">Filtruj</button>
        </div>
    </form>

    <!-- Tabela logów -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Typ Logu</th>
                <th>Wiadomość</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody id="systemLogs">
            <!-- Dane będą ładowane dynamicznie -->
        </tbody>
    </table>
    <p id="noLogsMessage" class="text-center text-muted" style="display:none;">Brak logów spełniających kryteria.</p>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterForm = document.getElementById("logFilterForm");
    const clearFilters = document.getElementById("clearFilters");

    // Obsługa filtrów
    filterForm.addEventListener("submit", function(e) {
        e.preventDefault();
        fetchLogs(new FormData(filterForm));
    });

    clearFilters.addEventListener("click", function() {
        filterForm.reset();
        fetchLogs();
    });

    // Pobieranie logów systemowych przez AJAX
    function fetchLogs(formData = null) {
        let url = "/api/admin/logs.php";
        if (formData) {
            url += "?" + new URLSearchParams(formData).toString();
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const logsTable = document.getElementById("systemLogs");
                logsTable.innerHTML = "";
                const noLogsMessage = document.getElementById("noLogsMessage");

                if (data.length === 0) {
                    noLogsMessage.style.display = "block";
                } else {
                    noLogsMessage.style.display = "none";
                    data.forEach(log => {
                        logsTable.innerHTML += `
                            <tr>
                                <td>${log.id}</td>
                                <td>${log.type}</td>
                                <td>${log.message}</td>
                                <td>${log.formatted_date}</td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd pobierania logów systemowych:", error));
    }

    // Automatyczne załadowanie logów po otwarciu strony
    fetchLogs();
});
</script>

<?php
// Fetch and display logs
$logs = []; // Assume this array is populated with log data from the database

foreach ($logs as &$log) {
    $log['formatted_date'] = date('Y-m-d H:i:s', strtotime($log['date']));
}
?>
