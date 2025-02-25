<?php
/*
|--------------------------------------------------------------------------
| Żądanie Resetu Hasła
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi wysłanie prośby o resetowanie hasła.
| Na podany adres e-mail zostanie wysłany link do resetu.
|
| Ścieżka: App/Views/auth/reset_request.php
|
| Zależy od:
| - JavaScript: /js/auth.js (obsługa AJAX, dynamiczne wysyłanie resetu)
| - CSS: /css/auth.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego wysyłania zapytania)
| - HTML, CSS (interfejs)
*/

require_once BASE_PATH . '/App/Helpers/SecurityHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../../../helpers/SecurityHelper.php';
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
    <title>Resetowanie Hasła</title>

    <!-- ✅ Bootstrap & Custom Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/css/auth.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">

<div class="auth-container bg-white p-4 rounded shadow-lg">
    <h1 class="text-center mb-4">Resetowanie Hasła</h1>

    <form id="resetRequestForm">
        <?= csrf_field(); ?>
        <div class="mb-3">
            <label for="email" class="form-label">Adres e-mail</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Wyślij link resetujący</button>
    </form>

    <div id="responseMessage" class="alert mt-3 d-none"></div>
    <p class="text-center mt-3"><a href="/auth/login.php">Pamiętasz hasło? Zaloguj się</a></p>
</div>

<!-- ✅ Bootstrap & Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/auth.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const resetRequestForm = document.getElementById("resetRequestForm");

    resetRequestForm.addEventListener("submit", function(e) {
        e.preventDefault();

        const formData = new FormData(resetRequestForm);
        formData.append("csrf_token", document.querySelector('input[name="csrf_token"]').value);

        requestPasswordReset(formData);
    });

    function requestPasswordReset(formData) {
        fetch("/api/auth/reset_request.php", {
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
                responseMessage.textContent = "✅ Link do resetowania hasła został wysłany na podany e-mail.";
            } else {
                responseMessage.className = "alert alert-danger";
                responseMessage.textContent = "❌ Błąd: " + data.error;
            }
        })
        .catch(error => {
            console.error("Błąd wysyłania resetu:", error);
        });
    }
});
</script>

</body>
</html>
