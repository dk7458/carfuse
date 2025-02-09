<?php
// Sprawdzenie sesji użytkownika

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}
?>

/*
|--------------------------------------------------------------------------
| Tworzenie Nowej Rezerwacji
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi dokonanie nowej rezerwacji pojazdu.
| Sprawdza dostępność pojazdów i umożliwia wybór metody płatności.
|
| Ścieżka: App/Views/bookings/create.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, dynamiczne sprawdzanie dostępności)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane pojazdów, dostępność, rezerwacje)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego sprawdzania dostępności)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Nowa Rezerwacja</h1>

<div class="bookings-create-container">
    <form id="bookingForm">
        <?= csrf_field() ?>

        <!-- Wybór pojazdu -->
        <div class="mb-3">
            <label for="vehicle" class="form-label">Wybierz pojazd</label>
            <select class="form-select" id="vehicle" name="vehicle" required>
                <option value="" disabled selected>Wybierz pojazd</option>
                <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?= esc($vehicle['id']) ?>"><?= esc($vehicle['name']) ?> – <?= esc($vehicle['price']) ?> PLN/dzień</option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Wybór daty rezerwacji -->
        <div class="row">
            <div class="col-md-6 col-12 mb-3">
                <label for="start_date" class="form-label">Data rozpoczęcia</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="col-md-6 col-12 mb-3">
                <label for="end_date" class="form-label">Data zakończenia</label>
                <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>
        </div>

        <!-- Metoda płatności -->
        <div class="mb-3">
            <label for="payment_method" class="form-label">Metoda płatności</label>
            <select class="form-select" id="payment_method" name="payment_method" required>
                <option value="card">Karta kredytowa</option>
                <option value="paypal">PayPal</option>
                <option value="transfer">Przelew bankowy</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Zarezerwuj</button>
    </form>

    <div id="responseMessage" class="alert mt-3" style="display:none;"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const bookingForm = document.getElementById("bookingForm");
    const responseMessage = document.getElementById("responseMessage");
    const vehicleSelect = document.getElementById("vehicle");
    const startDateInput = document.getElementById("start_date");
    const endDateInput = document.getElementById("end_date");

    // Sprawdzanie dostępności pojazdu
    vehicleSelect.addEventListener("change", function() {
        checkAvailability(vehicleSelect.value);
    });

    bookingForm.addEventListener("submit", function(e) {
        e.preventDefault();
        if (validateForm()) {
            submitBooking(new FormData(bookingForm));
        }
    });

    function checkAvailability(vehicleId) {
        if (!vehicleId) return;
        fetch(`/api/bookings/check_availability.php?vehicle_id=${vehicleId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.available) {
                    alert("Wybrany pojazd jest niedostępny w wybranym terminie.");
                    vehicleSelect.value = "";
                }
            })
            .catch(error => console.error("Błąd sprawdzania dostępności:", error));
    }

    function validateForm() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        if (startDate >= endDate) {
            alert("Data zakończenia musi być późniejsza niż data rozpoczęcia.");
            return false;
        }
        return true;
    }

    function submitBooking(formData) {
        fetch("/api/bookings/create.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            responseMessage.style.display = "block";
            if (data.success) {
                responseMessage.className = "alert alert-success";
                responseMessage.textContent = "Rezerwacja została pomyślnie dokonana!";
                setTimeout(() => window.location.href = "/dashboard", 2000);
            } else {
                responseMessage.className = "alert alert-danger";
                responseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            responseMessage.style.display = "block";
            responseMessage.className = "alert alert-danger";
            responseMessage.textContent = "Błąd połączenia z serwerem.";
            console.error("Błąd rezerwacji:", error);
        });
    }
});
</script>
