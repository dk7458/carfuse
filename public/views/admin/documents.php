<?php
require_once __DIR__ . '/../../../helpers/SecurityHelper.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>

/*
|--------------------------------------------------------------------------
| Panel Zarządzania Dokumentami
|--------------------------------------------------------------------------
| Ten plik umożliwia administratorowi przegląd i zarządzanie dokumentami 
| przesłanymi przez użytkowników. Obsługuje filtrowanie i podgląd plików.
|
| Ścieżka: App/Views/admin/documents.php
|
| Zależy od:
| - JavaScript: /js/admin.js (obsługa AJAX, filtrowanie, usuwanie dokumentów)
| - CSS: /css/admin.css (stylizacja interfejsu)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane dokumentów)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do pobierania danych)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Zarządzanie Dokumentami</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Lista Dokumentów</h3>
    </div>

    <!-- Filtry dokumentów -->
    <form id="documentFilterForm" class="row mt-4">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <input type="text" class="form-control" name="user_id" placeholder="ID Użytkownika">
        </div>
        <div class="col-md-3">
            <select class="form-control" name="document_type">
                <option value="">Typ dokumentu</option>
                <option value="contract">Umowa</option>
                <option value="invoice">Faktura</option>
                <option value="identity">Dowód tożsamości</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" name="upload_date" placeholder="Data przesłania">
        </div>
        <div class="col-md-3 text-end">
            <button type="submit" class="btn btn-primary">Filtruj</button>
        </div>
    </form>

    <!-- Tabela dokumentów -->
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Użytkownik</th>
                <th>Typ</th>
                <th>Plik</th>
                <th>Data przesłania</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody id="documentList">
            <!-- Dane będą ładowane dynamicznie -->
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterForm = document.getElementById("documentFilterForm");

    filterForm.addEventListener("submit", function(e) {
        e.preventDefault();
        fetchDocuments(new FormData(filterForm));
    });

    function fetchDocuments(formData = null) {
        let url = "/api/admin/documents.php";
        if (formData) {
            url += "?" + new URLSearchParams(formData).toString();
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const documentTable = document.getElementById("documentList");
                documentTable.innerHTML = "";

                if (data.length === 0) {
                    documentTable.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Brak dokumentów spełniających kryteria.</td></tr>`;
                } else {
                    data.forEach(doc => {
                        documentTable.innerHTML += `
                            <tr>
                                <td>${doc.id}</td>
                                <td>${doc.user}</td>
                                <td>${doc.type}</td>
                                <td><a href="${doc.file_url}" target="_blank">Podgląd</a></td>
                                <td>${doc.upload_date}</td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="deleteDocument(${doc.id})">Usuń</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd pobierania dokumentów:", error));
    }

    function deleteDocument(documentId) {
        if (!confirm("Czy na pewno chcesz usunąć ten dokument?")) return;

        fetch(`/api/admin/delete_document.php?id=${documentId}`, { method: "POST" })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Dokument został usunięty.");
                    fetchDocuments();
                } else {
                    alert("Błąd usuwania dokumentu: " + data.error);
                }
            })
            .catch(error => console.error("Błąd usuwania dokumentu:", error));
    }

    fetchDocuments();
});
</script>
