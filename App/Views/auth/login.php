<?php
/*
|--------------------------------------------------------------------------
| Logowanie Użytkownika
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi zalogowanie się do systemu.
|
| Ścieżka: App/Views/auth/login.php
|
| Zależy od:
| - JavaScript: /js/auth.js (obsługa AJAX, dynamiczna walidacja)
| - CSS: /css/auth.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego logowania)
| - HTML, CSS (interfejs)
*/

session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard");
    exit;
}
?>

<h1 class="text-center">Zaloguj się</h1>

<div class="auth-container">
    <form id="loginForm">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="email" class="form-label">Adres e-mail</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Hasło</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Zaloguj się</button>
    </form>
    <div id="responseMessage" class="alert mt-3" style="display:none;"></div>
    <p class="text-center mt-2"><a href="/auth/password_reset.php">Nie pamiętasz hasła?</a></p>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const loginForm = document.getElementById("loginForm");

    loginForm.addEventListener("submit", function(e) {
        e.preventDefault();
        loginUser(new FormData(loginForm));
    });

    function loginUser(formData) {
        fetch("/api/auth/login.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const responseMessage = document.getElementById("responseMessage");
            responseMessage.style.display = "block";

            if (data.success) {
                responseMessage.className = "alert alert-success";
                responseMessage.textContent = "Logowanie pomyślne! Przekierowywanie...";
                setTimeout(() => window.location.href = "/dashboard", 2000);
            } else {
                responseMessage.className = "alert alert-danger";
                responseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            console.error("Błąd logowania:", error);
        });
    }
});
</script>
