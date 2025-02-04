<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Moje dokumenty</h1>

<div class="documents-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Lista dokumentów</h3>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nazwa</th>
                    <th>Data dodania</th>
                    <th>Status</th>
                    <th>Opcje</th>
                </tr>
            </thead>
            <tbody id="documentsList">
                <!-- Dokumenty będą ładowane dynamicznie -->
            </tbody>
        </table>
    </div>

    <div class="text-center mt-4">
        <button id="uploadDocumentBtn" class="btn btn-primary">Dodaj dokument</button>
    </div>
</div>

<!-- Modal dodawania dokumentu -->
<div id="uploadDocumentModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Dodaj nowy dokument</h2>
        <form id="uploadDocumentForm" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="documentName" class="form-label">Nazwa dokumentu</label>
                <input type="text" id="documentName" name="documentName" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="documentFile" class="form-label">Plik dokumentu</label>
                <input type="file" id="documentFile" name="documentFile" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-success">Prześlij</button>
        </form>
    </div>
</div>

<script src="/js/documents.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
