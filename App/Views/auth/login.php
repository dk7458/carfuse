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

require_once BASE_PATH . '/App/Helpers/SecurityHelper.php'; // Ensure CSRF functions are loaded

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaloguj się</title>

    <!-- ✅ Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- ✅ Custom Authentication CSS -->
    <link rel="stylesheet" href="/../../../public/css/auth.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">

<div class="auth-container bg-white p-4 rounded shadow-lg">
    <h1 class="text-center mb-4">Zaloguj się</h1>

    <form id="loginForm">
        <?= csrf_field(); ?>
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
    
    <div id="responseMessage" class="alert mt-3 d-none"></div>
    <p class="text-center mt-3"><a href="/auth/password_reset.php">Nie pamiętasz hasła?</a></p>
</div>

<!-- ✅ Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- ✅ Custom Authentication Script -->
<script src="/js/auth.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const loginForm = document.getElementById("loginForm");

    loginForm.addEventListener("submit", function(e) {
        e.preventDefault();
        
        const formData = new FormData(loginForm);
        formData.append("csrf_token", document.querySelector('input[name="csrf_token"]').value); // Ensure CSRF token is included

        loginUser(formData);
    });

    function loginUser(formData) {
        fetch("/api/auth/login.php", {
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

</body>
</html>
