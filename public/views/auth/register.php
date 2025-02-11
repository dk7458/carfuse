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

require_once BASE_PATH . '/App/Helpers/SecurityHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../../../App/Helpers/SecurityHelper.php';
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
    
    <!-- ✅ Bootstrap & Custom Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/css/auth.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">

<div class="auth-container bg-white p-4 rounded shadow-lg">
    <h1 class="text-center mb-4">Zarejestruj się</h1>

    <form id="registerForm">
        <?= csrf_field(); ?>
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

    <div id="responseMessage" class="alert mt-3 d-none"></div>
    <p class="text-center mt-3"><a href="/auth/login.php">Masz już konto? Zaloguj się</a></p>
</div>

<!-- ✅ Bootstrap & Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/auth.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const registerForm = document.getElementById("registerForm");

    registerForm.addEventListener("submit", function(e) {
        e.preventDefault();
        
        const formData = new FormData(registerForm);
        formData.append("csrf_token", document.querySelector('input[name="csrf_token"]').value); // Ensure CSRF token is included
        
        registerUser(formData);
    });

    function registerUser(formData) {
        fetch("/api/auth/register.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const responseMessage = document.getElementById("responseMessage");
            responseMessage.classList.remove("d-none");
            responseMessage.style.display = "block";

            if (data.success) {
                responseMessage.className = "alert alert-success";
                responseMessage.textContent = "✅ Rejestracja udana! Przekierowywanie...";
                setTimeout(() => window.location.href = "/auth/login.php", 2000);
            } else {
                responseMessage.className = "alert alert-danger";
                responseMessage.textContent = "❌ Błąd: " + data.error;
            }
        })
        .catch(error => {
            console.error("Błąd rejestracji:", error);
        });
    }
});
</script>

</body>
</html>
