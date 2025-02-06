/*
|--------------------------------------------------------------------------
| Panel Zarządzania Użytkownikami
|--------------------------------------------------------------------------
| Ten plik umożliwia administratorowi przegląd i zarządzanie użytkownikami.
| Obsługuje filtrowanie, edycję, dodawanie i usuwanie użytkowników.
|
| Ścieżka: App/Views/admin/users.php
|
| Zależy od:
| - JavaScript: /js/admin.js (obsługa AJAX, edycja, usuwanie)
| - CSS: /css/admin.css (stylizacja interfejsu)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane użytkowników)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do pobierania danych)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Zarządzanie Użytkownikami</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Lista Użytkowników</h3>
        <button class="btn btn-primary" id="addUserBtn">Dodaj Użytkownika</button>
    </div>

    <!-- Filtry użytkowników -->
    <form id="userFilterForm" class="row mt-4">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <select class="form-control" name="role">
                <option value="">Wybierz rolę</option>
                <option value="user">Użytkownik</option>
                <option value="admin">Administrator</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-control" name="status">
                <option value="">Status</option>
                <option value="active">Aktywny</option>
                <option value="inactive">Nieaktywny</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" name="registration_date" placeholder="Data rejestracji">
        </div>
        <div class="col-md-3 text-end">
            <button type="submit" class="btn btn-primary">Filtruj</button>
        </div>
    </form>

    <!-- Tabela użytkowników -->
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imię i nazwisko</th>
                <th>Email</th>
                <th>Rola</th>
                <th>Status</th>
                <th>Data rejestracji</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody id="userList">
            <!-- Dane będą ładowane dynamicznie -->
        </tbody>
    </table>
</div>

<!-- Modal dodawania/edycji użytkownika -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Dodaj Użytkownika</h2>
        <form id="userForm">
            <?= csrf_field() ?>
            <input type="hidden" id="userId" name="userId">

            <div class="form-group">
                <label for="name">Imię i nazwisko</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email">Adres e-mail</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="role">Rola</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="user">Użytkownik</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="active">Aktywny</option>
                    <option value="inactive">Nieaktywny</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success">Zapisz</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterForm = document.getElementById("userFilterForm");
    const addUserBtn = document.getElementById("addUserBtn");

    filterForm.addEventListener("submit", function(e) {
        e.preventDefault();
        fetchUsers(new FormData(filterForm));
    });

    addUserBtn.addEventListener("click", function() {
        openUserModal();
    });

    function fetchUsers(formData = null) {
        let url = "/api/admin/users.php";
        if (formData) {
            url += "?" + new URLSearchParams(formData).toString();
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const userTable = document.getElementById("userList");
                userTable.innerHTML = "";

                if (data.length === 0) {
                    userTable.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Brak użytkowników spełniających kryteria.</td></tr>`;
                } else {
                    data.forEach(user => {
                        userTable.innerHTML += `
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.name}</td>
                                <td>${user.email}</td>
                                <td>${user.role}</td>
                                <td>${user.status}</td>
                                <td>${user.registration_date}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="editUser(${user.id})">Edytuj</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">Usuń</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd pobierania użytkowników:", error));
    }

    fetchUsers();
});
</script>
