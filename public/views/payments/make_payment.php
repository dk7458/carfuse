<?php
/*
|--------------------------------------------------------------------------
| Dokonaj Płatności
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi dokonanie nowej płatności za rezerwacje
| lub inne usługi dostępne w systemie.
|
| Ścieżka: App/Views/payments/make_payment.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, dynamiczne przetwarzanie płatności)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (obsługa transakcji)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego przetwarzania płatności)
| - HTML, CSS (interfejs)
*/


if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
?>

<h1 class="text-center">Dokonaj Płatności</h1>

<div class="payments-make-container">
    <form id="paymentForm">
        <?= csrf_field() ?>

        <!-- Wybór kwoty -->
        <div class="mb-3">
            <label for="amount" class="form-label">Kwota płatności (PLN)</label>
            <input type="number" class="form-control" id="amount" name="amount" min="1" required>
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

        <div id="cardDetails" style="display:none;">
            <div class="mb-3">
                <label for="card_number" class="form-label">Numer karty</label>
                <input type="text" class="form-control" id="card_number" name="card_number" pattern="\d{16}" placeholder="1234 5678 9012 3456">
            </div>
            <div class="mb-3 row">
                <div class="col">
                    <label for="expiry_date" class="form-label">Data ważności</label>
                    <input type="text" class="form-control" id="expiry_date" name="expiry_date" placeholder="MM/YY">
                </div>
                <div class="col">
                    <label for="cvv" class="form-label">CVV</label>
                    <input type="text" class="form-control" id="cvv" name="cvv" pattern="\d{3}" placeholder="123">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Zapłać</button>
    </form>

    <div id="responseMessage" class="alert mt-3" style="display:none;"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const paymentTypeSelect = document.getElementById("payment_method");
    const cardDetails = document.getElementById("cardDetails");

    paymentTypeSelect.addEventListener("change", function() {
        cardDetails.style.display = this.value === "card" ? "block" : "none";
    });

    const paymentForm = document.getElementById("paymentForm");

    paymentForm.addEventListener("submit", function(e) {
        e.preventDefault();
        submitPayment(new FormData(paymentForm));
    });

    function submitPayment(formData) {
        fetch("/api/user/make_payment.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const responseMessage = document.getElementById("responseMessage");
            responseMessage.style.display = "block";

            if (data.success) {
                responseMessage.className = "alert alert-success";
                responseMessage.textContent = "Płatność została pomyślnie zrealizowana!";
                setTimeout(() => window.location.href = "/payments/history", 2000);
            } else {
                responseMessage.className = "alert alert-danger";
                responseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            const responseMessage = document.getElementById("responseMessage");
            responseMessage.style.display = "block";
            responseMessage.className = "alert alert-danger";
            responseMessage.textContent = "Błąd połączenia z serwerem.";
            console.error("Błąd płatności:", error);
        });
    }
});
</script>
