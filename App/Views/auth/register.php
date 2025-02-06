<?php
/*
|--------------------------------------------------------------------------
| Rejestracja Użytkownika
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi założenie konta.
|
| Ścieżka: App/Views/auth/register.php
|
| Zależy od:
| - JavaScript: /js/auth.js (obsługa AJAX, dynamiczna walidacja)
| - CSS: /css/auth.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego przetwarzania rejestracji)
| - HTML, CSS (interfejs)
*/

session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard");
    exit;
}
?>

<h1 class="text-center">Rejestracja</h1>

<div class="auth-container">
    <form id="registerForm">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="name" class="form-label">Imię i nazwisko</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Adres e-mail</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Hasło</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Potwierdź hasło</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Zarejestruj się</button>
    </form>
    <div id="responseMessage" class="alert mt-3" style="display:none;"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const registerForm = document.getElementById("registerForm");

    registerForm.addEventListener("submit", function(e) {
        e.preventDefault();
        registerUser(new FormData(registerForm));
    });

    function registerUser(formData) {
        fetch("/api/auth/register.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const responseMessage = document.getElementById("responseMessage");
            responseMessage.style.display = "block";

            if (data.success) {
                responseMessage.className = "alert alert-success";
                responseMessage.textContent = "Rejestracja udana! Przekierowywanie...";
                setTimeout(() => window.location.href = "/auth/login.php", 2000);
            } else {
                responseMessage.className = "alert alert-danger";
                responseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            console.error("Błąd rejestracji:", error);
        });
    }
});
</script>
