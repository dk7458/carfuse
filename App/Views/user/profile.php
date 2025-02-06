/*
|--------------------------------------------------------------------------
| Edycja Profilu Użytkownika
|--------------------------------------------------------------------------
| Ten plik pozwala użytkownikowi aktualizować swoje dane, zmieniać hasło
| oraz zarządzać swoim zdjęciem profilowym.
|
| Ścieżka: App/Views/user/profile.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, walidacja formularzy)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane użytkownika)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego zapisu)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Mój Profil</h1>

<div class="user-profile-container">
    <div class="row">
        <!-- Edycja Danych Użytkownika -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h4>Edycja Danych</h4>
                    <form id="profileForm">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="name" class="form-label">Imię i nazwisko</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= esc($user['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Adres e-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= esc($user['email']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Zapisz zmiany</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Zmiana Hasła -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h4>Zmiana Hasła</h4>
                    <form id="passwordForm">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Obecne hasło</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nowe hasło</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Potwierdź nowe hasło</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Zmień hasło</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Zarządzanie Awatarem -->
    <div class="row mt-4">
        <div class="col-md-6 offset-md-3">
            <div class="card shadow text-center">
                <div class="card-body">
                    <h4>Zdjęcie Profilowe</h4>
                    <img src="<?= esc($user['avatar_url'] ?? '/images/default-avatar.png') ?>" class="profile-avatar img-thumbnail mb-3" width="150">
                    <form id="avatarForm" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="file" class="form-control mb-2" id="avatar" name="avatar" accept="image/*">
                        <button type="submit" class="btn btn-success">Prześlij nowe zdjęcie</button>
                        <button type="button" class="btn btn-danger" onclick="deleteAvatar()">Usuń zdjęcie</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const profileForm = document.getElementById("profileForm");
    const passwordForm = document.getElementById("passwordForm");
    const avatarForm = document.getElementById("avatarForm");

    profileForm.addEventListener("submit", function(e) {
        e.preventDefault();
        updateProfile(new FormData(profileForm));
    });

    passwordForm.addEventListener("submit", function(e) {
        e.preventDefault();
        updatePassword(new FormData(passwordForm));
    });

    avatarForm.addEventListener("submit", function(e) {
        e.preventDefault();
        updateAvatar(new FormData(avatarForm));
    });

    function updateProfile(formData) {
        fetch("/api/user/update_profile.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? "Dane zaktualizowane!" : "Błąd: " + data.error);
        })
        .catch(error => console.error("Błąd aktualizacji profilu:", error));
    }

    function updatePassword(formData) {
        fetch("/api/user/update_password.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? "Hasło zmienione!" : "Błąd: " + data.error);
        })
        .catch(error => console.error("Błąd zmiany hasła:", error));
    }

    function updateAvatar(formData) {
        fetch("/api/user/update_avatar.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Zdjęcie profilowe zaktualizowane!");
                location.reload();
            } else {
                alert("Błąd: " + data.error);
            }
        })
        .catch(error => console.error("Błąd aktualizacji awatara:", error));
    }

    function deleteAvatar() {
        if (!confirm("Czy na pewno chcesz usunąć zdjęcie profilowe?")) return;

        fetch("/api/user/delete_avatar.php", { method: "POST" })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Zdjęcie profilowe usunięte!");
                location.reload();
            } else {
                alert("Błąd: " + data.error);
            }
        })
        .catch(error => console.error("Błąd usuwania awatara:", error));
    }
});
</script>
