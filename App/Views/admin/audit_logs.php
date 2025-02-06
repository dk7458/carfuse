/*
|--------------------------------------------------------------------------
| Logi Audytowe Administratora
|--------------------------------------------------------------------------
| Ten plik odpowiada za wyświetlanie logów audytowych systemu. Administrator
| może filtrować historię działań użytkowników według ID, rezerwacji, daty i IP.
|
| Ścieżka: App/Views/admin/audit_logs.php
|
| Zależy od:
| - JavaScript: admin.js (obsługa logów, AJAX)
| - CSS: admin.css (stylizacja tabeli, formularzy)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane pobierane z bazy)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX, dynamiczne ładowanie danych)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Logi Audytowe</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Historia Zdarzeń w Systemie</h3>
        <button class="btn btn-secondary" id="clearFilters">Wyczyść Filtry</button>
    </div>

    <!-- Filtry -->
    <form id="filterForm" class="row mb-4">
        <?= csrf_field() ?>
        <div class="col-md-2">
            <input type="text" class="form-control" name="user_id" placeholder="ID Użytkownika">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control" name="booking_id" placeholder="ID Rezerwacji">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="start_date" placeholder="Data początkowa">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="end_date" placeholder="Data końcowa">
        </div>
        <div class="col-md-2">
            <select class="form-control" name="action_type">
                <option value="">Rodzaj akcji</option>
                <option value="login">Logowanie</option>
                <option value="update">Aktualizacja</option>
                <option value="delete">Usunięcie</option>
            </select>
        </div>
        <div class="col-md-2 text-end">
            <button type="submit" class="btn btn-primary">Filtruj</button>
        </div>
    </form>

    <!-- Tabela Logów -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Użytkownik</th>
                <th>Akcja</th>
                <th>Opis</th>
                <th>ID Rezerwacji</th>
                <th>Adres IP</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody id="auditLogs">
            <!-- Dane będą ładowane dynamicznie -->
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterForm = document.getElementById("filterForm");
    const clearFilters = document.getElementById("clearFilters");

    // Obsługa filtrów
    filterForm.addEventListener("submit", function(e) {
        e.preventDefault();
        fetchAuditLogs(new FormData(filterForm));
    });

    clearFilters.addEventListener("click", function() {
        filterForm.reset();
        fetchAuditLogs();
    });

    // Pobieranie logów audytowych przez AJAX
    function fetchAuditLogs(formData = null) {
        let url = "/api/admin/audit_logs.php";
        if (formData) {
            url += "?" + new URLSearchParams(formData).toString();
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const auditLogsTable = document.getElementById("auditLogs");
                auditLogsTable.innerHTML = "";
                data.forEach(log => {
                    auditLogsTable.innerHTML += `
                        <tr>
                            <td>${log.id}</td>
                            <td>${log.user}</td>
                            <td>${log.action}</td>
                            <td>${log.details}</td>
                            <td>${log.booking_id || '-'}</td>
                            <td>${log.ip_address}</td>
                            <td>${log.created_at}</td>
                        </tr>
                    `;
                });
            })
            .catch(error => console.error("Błąd pobierania logów audytowych:", error));
    }

    // Automatyczne załadowanie logów po otwarciu strony
    fetchAuditLogs();
});
</script>
