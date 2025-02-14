<?php
// Verify admin session and include necessary helpers
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

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
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logi Audytowe</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="path/to/bootstrap.min.css">
    <link rel="stylesheet" href="path/to/admin.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Logi Audytowe</h1>
        <div class="card my-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Historia Zdarzeń w Systemie</h3>
                <button class="btn btn-secondary" id="clearFilters">Wyczyść Filtry</button>
            </div>
            <div class="card-body">
                <!-- Filtry -->
                <form id="filterForm" class="row mb-4">
                    <?= csrf_field() ?>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="user_id" placeholder="ID Użytkownika" value="<?= filter_input(INPUT_GET, 'user_id', FILTER_SANITIZE_STRING) ?: '' ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="booking_id" placeholder="ID Rezerwacji" value="<?= filter_input(INPUT_GET, 'booking_id', FILTER_SANITIZE_STRING) ?: '' ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="start_date" placeholder="Data początkowa" value="<?= filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING) ?: '' ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="end_date" placeholder="Data końcowa" value="<?= filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING) ?: '' ?>">
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
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
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
                            <!-- ... dane ładowane przez AJAX ... -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination (sample placeholder) -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center" id="pagination">
                        <!-- AJAX should populate pagination links here -->
                        <li class="page-item disabled"><a class="page-link" href="#">Poprzednia</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">Następna</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JS and dependencies -->
    <script src="path/to/bootstrap.bundle.min.js"></script>
    <script src="path/to/admin.js"></script>
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
                    data.logs.forEach(log => {
                        auditLogsTable.innerHTML += `
                            <tr>
                                <td>${log.id}</td>
                                <td>${log.user}</td>
                                <td>${log.action}</td>
                                <td>${log.details}</td>
                                <td>${log.booking_id || '-'}</td>
                                <td>${log.ip_address}</td>
                                <td>${log.formatted_date}</td>
                            </tr>
                        `;
                    });
                    // Update pagination if available
                    if(data.pagination){
                        document.getElementById("pagination").innerHTML = data.pagination;
                    }
                })
                .catch(error => console.error("Błąd pobierania logów audytowych:", error));
        }

        // Automatyczne załadowanie logów po otwarciu strony
        fetchAuditLogs();
    });
    </script>
</body>
</html>
