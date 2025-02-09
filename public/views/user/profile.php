<?php
require_once __DIR__ . '/../../../App/Helpers/SecurityHelper.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}
$page = 'profile';

?>

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
        <div class="col-md-6 mb-4">
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
                    <div id="profileResponseMessage" class="alert mt-3" style="display:none;"></div>
                </div>
            </div>
        </div>

        <!-- Zmiana Hasła -->
        <div class="col-md-6 mb-4">
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
                    <div id="passwordResponseMessage" class="alert mt-3" style="display:none;"></div>
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
                    <img id="profileAvatar" src="<?= esc($user['avatar_url'] ?? '/images/default-avatar.png') ?>" class="profile-avatar img-thumbnail mb-3" width="150">
                    <form id="avatarForm" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="file" class="form-control mb-2" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(event)">
                        <button type="submit" class="btn btn-success">Prześlij nowe zdjęcie</button>
                        <button type="button" class="btn btn-danger" onclick="deleteAvatar()">Usuń zdjęcie</button>
                    </form>
                    <div id="avatarResponseMessage" class="alert mt-3" style="display:none;"></div>
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
    const profileResponseMessage = document.getElementById("profileResponseMessage");
    const passwordResponseMessage = document.getElementById("passwordResponseMessage");
    const avatarResponseMessage = document.getElementById("avatarResponseMessage");

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
            profileResponseMessage.style.display = "block";
            if (data.success) {
                profileResponseMessage.className = "alert alert-success";
                profileResponseMessage.textContent = "Dane zaktualizowane!";
            } else {
                profileResponseMessage.className = "alert alert-danger";
                profileResponseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            profileResponseMessage.style.display = "block";
            profileResponseMessage.className = "alert alert-danger";
            profileResponseMessage.textContent = "Błąd połączenia z serwerem.";
            console.error("Błąd aktualizacji profilu:", error);
        });
    }

    function updatePassword(formData) {
        fetch("/api/user/update_password.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            passwordResponseMessage.style.display = "block";
            if (data.success) {
                passwordResponseMessage.className = "alert alert-success";
                passwordResponseMessage.textContent = "Hasło zmienione!";
            } else {
                passwordResponseMessage.className = "alert alert-danger";
                passwordResponseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            passwordResponseMessage.style.display = "block";
            passwordResponseMessage.className = "alert alert-danger";
            passwordResponseMessage.textContent = "Błąd połączenia z serwerem.";
            console.error("Błąd zmiany hasła:", error);
        });
    }

    function updateAvatar(formData) {
        fetch("/api/user/update_avatar.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            avatarResponseMessage.style.display = "block";
            if (data.success) {
                avatarResponseMessage.className = "alert alert-success";
                avatarResponseMessage.textContent = "Zdjęcie profilowe zaktualizowane!";
                location.reload();
            } else {
                avatarResponseMessage.className = "alert alert-danger";
                avatarResponseMessage.textContent = "Błąd: " + data.error;
            }
        })
        .catch(error => {
            avatarResponseMessage.style.display = "block";
            avatarResponseMessage.className = "alert alert-danger";
            avatarResponseMessage.textContent = "Błąd połączenia z serwerem.";
            console.error("Błąd aktualizacji awatara:", error);
        });
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

    function previewAvatar(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('profileAvatar');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
});
</script>
