<?php
/*
|--------------------------------------------------------------------------
| Resetowanie Hasła
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi ustawienie nowego hasła po kliknięciu w link
| resetujący, który został wysłany na jego e-mail.
|
| Ścieżka: App/Views/auth/password_reset.php
|
| Zależy od:
| - JavaScript: /js/auth.js (obsługa AJAX, dynamiczne przetwarzanie resetu)
| - CSS: /css/auth.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznej zmiany hasła)
| - HTML, CSS (interfejs)
*/


if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard");
    exit;
}

// Pobranie tokenu resetującego z URL
$token = $_GET['token'] ?? null;
if (!$token) {
    die("Nieprawidłowy link resetujący.");
}
?>

<h1 class="text-center">Ustaw nowe hasło</h1>

<div class="auth-container">
    <form id="passwordResetForm">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3">
            <label for="new_password" class="form-label">Nowe hasło</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Potwierdź nowe hasło</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Zmień hasło</button>
    </form>
    <div id="responseMessage" class="alert mt-3" style="display:none;"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const passwordResetForm = document.getElementById("passwordResetForm");

    passwordResetForm.addEventListener("submit", function(e) {
        e.preventDefault();
        resetPassword(new FormData(passwordResetForm));
    });

    function resetPassword(formData) {
        fetch("/api/auth/password_reset.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const responseMessage = document.getElementById("responseMessage");
            responseMessage.style.display = "block";

            if (data.success) {
                responseMessage.className = "alert alert-success";
                responseMessage.textContent = "Hasło zostało zmienione! Możesz się teraz zalogować.";
                setTimeout(() => window.location.href = "/auth/login.php", 2000);
            } else {
                responseMessage.className = "alert alert-danger";
                responseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            console.error("Błąd zmiany hasła:", error);
        });
    }
});
</script>
