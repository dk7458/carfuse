<?php
// Sprawdzenie sesji użytkownika
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}
?>

/*
|--------------------------------------------------------------------------
| Lista Rezerwacji Użytkownika
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi przeglądanie swoich rezerwacji, filtrowanie
| ich oraz sprawdzanie statusu każdej rezerwacji.
|
| Ścieżka: App/Views/bookings/view.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, dynamiczne ładowanie rezerwacji)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane o rezerwacjach)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego ładowania rezerwacji)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Moje Rezerwacje</h1>

<div class="bookings-container">
    <!-- Filtry rezerwacji -->
    <form id="bookingFilterForm" class="row mt-4">
        <?= csrf_field() ?>
        <div class="col-md-4">
            <select class="form-control" name="status">
                <option value="">Wybierz status</option>
                <option value="active">Aktywna</option>
                <option value="completed">Zakończona</option>
                <option value="cancelled">Anulowana</option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="date" class="form-control" name="start_date" placeholder="Data początkowa">
        </div>
        <div class="col-md-4 text-end">
            <button type="submit" class="btn btn-primary">Filtruj</button>
        </div>
    </form>

    <!-- Tabela rezerwacji -->
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Pojazd</th>
                <th>Data rozpoczęcia</th>
                <th>Data zakończenia</th>
                <th>Status</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody id="bookingList">
            <!-- Dane będą ładowane dynamicznie -->
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterForm = document.getElementById("bookingFilterForm");

    filterForm.addEventListener("submit", function(e) {
        e.preventDefault();
        fetchBookings(new FormData(filterForm));
    });

    function fetchBookings(formData = null) {
        let url = "/api/user/bookings.php";
        if (formData) {
            url += "?" + new URLSearchParams(formData).toString();
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const bookingTable = document.getElementById("bookingList");
                bookingTable.innerHTML = "";

                if (data.length === 0) {
                    bookingTable.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Brak rezerwacji spełniających kryteria.</td></tr>`;
                } else {
                    data.forEach(booking => {
                        let statusClass = "";
                        switch (booking.status) {
                            case "active":
                                statusClass = "text-success";
                                break;
                            case "completed":
                                statusClass = "text-secondary";
                                break;
                            case "cancelled":
                                statusClass = "text-danger";
                                break;
                        }
                        bookingTable.innerHTML += `
                            <tr>
                                <td>${booking.id}</td>
                                <td>${booking.vehicle}</td>
                                <td>${booking.start_date}</td>
                                <td>${booking.end_date}</td>
                                <td class="${statusClass}">${booking.status}</td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="viewBooking(${booking.id})">Podgląd</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd pobierania rezerwacji:", error));
    }

    function viewBooking(bookingId) {
        fetch(`/api/user/booking_details.php?id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                alert(`Szczegóły rezerwacji:\n\nPojazd: ${data.vehicle}\nOd: ${data.start_date}\nDo: ${data.end_date}\nStatus: ${data.status}`);
            })
            .catch(error => console.error("Błąd pobierania szczegółów rezerwacji:", error));
    }

    fetchBookings();
});
</script>
