<?php
/*
|--------------------------------------------------------------------------
| Metody Płatności Użytkownika
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi zarządzanie zapisanymi metodami płatności.
| Obsługuje dodawanie nowych metod oraz usuwanie zapisanych kart czy kont PayPal.
|
| Ścieżka: App/Views/payments/methods.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, dynamiczne pobieranie metod płatności)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane metod płatności)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego pobierania metod płatności)
| - HTML, CSS (interfejs)
*/

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
?>

<h1 class="text-center">Moje Metody Płatności</h1>

<div class="payments-methods-container">
    <!-- Formularz dodawania metody płatności -->
    <form id="addPaymentMethodForm">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="payment_type" class="form-label">Wybierz metodę</label>
            <select class="form-select" id="payment_type" name="payment_type" required>
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

        <button type="submit" class="btn btn-primary w-100">Dodaj metodę płatności</button>
    </form>

    <h3 class="mt-4">Zapisane Metody Płatności</h3>
    <ul id="paymentMethodsList" class="list-group">
        <!-- Metody płatności ładowane dynamicznie -->
    </ul>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const paymentTypeSelect = document.getElementById("payment_type");
    const cardDetails = document.getElementById("cardDetails");

    paymentTypeSelect.addEventListener("change", function() {
        cardDetails.style.display = this.value === "card" ? "block" : "none";
    });

    const addPaymentForm = document.getElementById("addPaymentMethodForm");

    addPaymentForm.addEventListener("submit", function(e) {
        e.preventDefault();
        addPaymentMethod(new FormData(addPaymentForm));
    });

    function loadPaymentMethods() {
        fetch("/api/user/get_payment_methods.php")
            .then(response => response.json())
            .then(data => {
                const methodList = document.getElementById("paymentMethodsList");
                methodList.innerHTML = "";

                if (data.length > 0) {
                    data.forEach(method => {
                        methodList.innerHTML += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>${method.type === "card" ? "💳 Karta kredytowa" : method.type === "paypal" ? "🅿️ PayPal" : "🏦 Przelew bankowy"}</span>
                                <button class="btn btn-sm btn-danger" onclick="deletePaymentMethod(${method.id})">Usuń</button>
                            </li>
                        `;
                    });
                } else {
                    methodList.innerHTML = `<li class="list-group-item text-muted">Brak zapisanych metod płatności</li>`;
                }
            })
            .catch(error => console.error("Błąd ładowania metod płatności:", error));
    }

    function addPaymentMethod(formData) {
        fetch("/api/user/add_payment_method.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Metoda płatności została dodana!");
                loadPaymentMethods();
            } else {
                alert("Błąd: " + data.error);
            }
        })
        .catch(error => console.error("Błąd dodawania metody płatności:", error));
    }

    function deletePaymentMethod(methodId) {
        if (!confirm("Czy na pewno chcesz usunąć tę metodę płatności?")) return;

        fetch(`/api/user/delete_payment_method.php?id=${methodId}`, { method: "POST" })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Metoda płatności została usunięta!");
                loadPaymentMethods();
            } else {
                alert("Błąd: " + data.error);
            }
        })
        .catch(error => console.error("Błąd usuwania metody płatności:", error));
    }

    loadPaymentMethods();
});
</script>
