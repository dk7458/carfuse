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

session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard");
    exit;
}
?>

<h1 class="text-center">Resetowanie Hasła</h1>

<div class="auth-container">
    <form id="resetRequestForm">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="email" class="form-label">Adres e-mail</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Wyślij link resetujący</button>
    </form>
    <div id="responseMessage" class="alert mt-3" style="display:none;"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const resetRequestForm = document.getElementById("resetRequestForm");

    resetRequestForm.addEventListener("submit", function(e) {
        e.preventDefault();
        requestPasswordReset(new FormData(resetRequestForm));
    });

    function requestPasswordReset(formData) {
        fetch("/api/auth/reset_request.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const responseMessage = document.getElementById("responseMessage");
            responseMessage.style.display = "block";

            if (data.success) {
                responseMessage.className = "alert alert-success";
                responseMessage.textContent = "Link do resetowania hasła został wysłany na podany e-mail.";
            } else {
                responseMessage.className = "alert alert-danger";
                responseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            console.error("Błąd wysyłania resetu:", error);
        });
    }
});
</script>
