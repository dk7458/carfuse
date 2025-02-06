/*
|--------------------------------------------------------------------------
| Ustawienia Systemowe Administratora
|--------------------------------------------------------------------------
| Ten plik umożliwia administratorowi konfigurację systemu – ustawienia
| ogólne, tryb konserwacji, strefę czasową oraz konfigurację API.
|
| Ścieżka: App/Views/admin/settings.php
|
| Zależy od:
| - JavaScript: /js/admin.js (obsługa formularza ustawień, AJAX)
| - CSS: /css/admin.css (stylizacja interfejsu)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (przechowywanie ustawień)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do zapisu ustawień)
| - HTML, CSS (interfejs)
*/

<?php
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Improved form validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    if (empty($_POST['setting_name'])) {
        $errors[] = 'Setting name is required';
    }

    if (empty($_POST['setting_value'])) {
        $errors[] = 'Setting value is required';
    }

    if (empty($errors)) {
        // Save settings logic
        // ...existing code...
    } else {
        foreach ($errors as $error) {
            echo "<p>$error</p>";
        }
    }
}
?>

<h1 class="text-center">Ustawienia Systemowe</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Konfiguracja systemu</h3>
        <button class="btn btn-secondary" id="resetSettings">Resetuj</button>
    </div>

    <!-- Formularz ustawień systemowych -->
    <form id="settingsForm" class="mt-4">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="site_name" class="form-label">Nazwa strony</label>
            <input type="text" id="site_name" name="site_name" class="form-control" placeholder="Podaj nazwę strony" value="<?= esc($settings['site_name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="admin_email" class="form-label">E-mail administratora</label>
            <input type="email" id="admin_email" name="admin_email" class="form-control" placeholder="Podaj e-mail administratora" value="<?= esc($settings['admin_email'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="logo_url" class="form-label">Logo strony (URL)</label>
            <input type="url" id="logo_url" name="logo_url" class="form-control" placeholder="Podaj URL logo" value="<?= esc($settings['logo_url'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="timezone" class="form-label">Strefa czasowa</label>
            <select id="timezone" name="timezone" class="form-select">
                <?php foreach (timezone_identifiers_list() as $tz): ?>
                    <option value="<?= esc($tz) ?>" <?= ($settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= esc($tz) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="log_limit" class="form-label">Maksymalna liczba logów</label>
            <input type="number" id="log_limit" name="log_limit" class="form-control" value="<?= esc($settings['log_limit'] ?? 1000) ?>" min="100">
        </div>

        <div class="mb-3">
            <label for="api_key" class="form-label">Klucz API</label>
            <input type="text" id="api_key" name="api_key" class="form-control" placeholder="Podaj klucz API" value="<?= esc($settings['api_key'] ?? '') ?>">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" <?= isset($settings['maintenance_mode']) && $settings['maintenance_mode'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="maintenance_mode">Tryb konserwacji</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Zapisz zmiany</button>
    </form>

    <div id="responseMessage" class="alert mt-3" style="display:none;"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const settingsForm = document.getElementById("settingsForm");
    const responseMessage = document.getElementById("responseMessage");
    const resetSettings = document.getElementById("resetSettings");

    settingsForm.addEventListener("submit", function(e) {
        e.preventDefault();
        saveSettings(new FormData(settingsForm));
    });

    resetSettings.addEventListener("click", function() {
        if (confirm("Czy na pewno chcesz przywrócić domyślne ustawienia?")) {
            fetch("/api/admin/reset_settings.php", { method: "POST" })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    responseMessage.style.display = "block";
                    responseMessage.className = "alert alert-danger";
                    responseMessage.textContent = "Błąd resetowania ustawień.";
                }
            })
            .catch(error => console.error("Błąd resetowania ustawień:", error));
        }
    });

    function saveSettings(formData) {
        let url = "/api/admin/save_settings.php";

        fetch(url, {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            responseMessage.style.display = "block";
            if (data.success) {
                responseMessage.className = "alert alert-success";
                responseMessage.textContent = "Ustawienia zapisane pomyślnie!";
            } else {
                responseMessage.className = "alert alert-danger";
                responseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            responseMessage.style.display = "block";
            responseMessage.className = "alert alert-danger";
            responseMessage.textContent = "Błąd połączenia z serwerem.";
            console.error("Błąd zapisywania ustawień:", error);
        });
    }
});
</script>
