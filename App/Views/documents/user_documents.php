<?php
// Sprawdzenie sesji użytkownika
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}
?>

/*
|--------------------------------------------------------------------------
| Dokumenty Użytkownika
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi przeglądanie dokumentów, ich przesyłanie
| oraz usuwanie. Pliki mogą być np. umowami, fakturami czy dokumentami tożsamości.
|
| Ścieżka: App/Views/documents/user_documents.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, przesyłanie, usuwanie)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane dokumentów)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego ładowania i usuwania plików)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Moje Dokumenty</h1>

<div class="documents-container">
    <!-- Przesyłanie nowego dokumentu -->
    <form id="uploadDocumentForm" enctype="multipart/form-data" class="mb-4">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="document" class="form-label">Prześlij dokument (PDF, JPG, PNG)</label>
            <input type="file" class="form-control" id="document" name="document" accept=".pdf, .jpg, .jpeg, .png" required>
        </div>
        <button type="submit" class="btn btn-success">Prześlij</button>
    </form>

    <!-- Lista dokumentów -->
    <h3 class="mt-4">Twoje Dokumenty</h3>
    <ul id="documentList" class="list-group">
        <!-- Dokumenty ładowane dynamicznie -->
    </ul>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const uploadForm = document.getElementById("uploadDocumentForm");

    uploadForm.addEventListener("submit", function(e) {
        e.preventDefault();
        const fileInput = document.getElementById("document");
        const file = fileInput.files[0];
        if (file && (file.type === "application/pdf" || file.type.startsWith("image/"))) {
            uploadDocument(new FormData(uploadForm));
        } else {
            alert("Nieprawidłowy typ pliku. Dozwolone formaty: PDF, JPG, PNG.");
        }
    });

    function loadDocuments() {
        fetch("/api/user/get_documents.php")
            .then(response => response.json())
            .then(data => {
                const documentList = document.getElementById("documentList");
                documentList.innerHTML = "";

                if (data.length > 0) {
                    data.forEach(doc => {
                        documentList.innerHTML += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="${doc.file_url}" target="_blank">${doc.name}</a>
                                <button class="btn btn-sm btn-danger" onclick="deleteDocument(${doc.id})">Usuń</button>
                            </li>
                        `;
                    });
                } else {
                    documentList.innerHTML = `<li class="list-group-item text-muted">Brak dokumentów</li>`;
                }
            })
            .catch(error => {
                console.error("Błąd ładowania dokumentów:", error);
                alert("Błąd ładowania dokumentów.");
            });
    }

    function uploadDocument(formData) {
        fetch("/api/user/upload_document.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Dokument przesłany pomyślnie!");
                loadDocuments();
            } else {
                alert("Błąd: " + data.error);
            }
        })
        .catch(error => {
            console.error("Błąd przesyłania dokumentu:", error);
            alert("Błąd przesyłania dokumentu.");
        });
    }

    function deleteDocument(documentId) {
        if (!confirm("Czy na pewno chcesz usunąć ten dokument?")) return;

        fetch(`/api/user/delete_document.php?id=${documentId}`, { method: "POST" })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Dokument usunięty!");
                loadDocuments();
            } else {
                alert("Błąd: " + data.error);
            }
        })
        .catch(error => {
            console.error("Błąd usuwania dokumentu:", error);
            alert("Błąd usuwania dokumentu.");
        });
    }

    loadDocuments();
});
</script>
