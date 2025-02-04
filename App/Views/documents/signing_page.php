<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Podpisywanie dokumentów</h1>

<div class="documents-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Szczegóły dokumentu</h3>

        <table class="table table-bordered">
            <tr>
                <th>ID dokumentu</th>
                <td id="documentId">Ładowanie...</td>
            </tr>
            <tr>
                <th>Nazwa</th>
                <td id="documentName">Ładowanie...</td>
            </tr>
            <tr>
                <th>Data przesłania</th>
                <td id="uploadDate">Ładowanie...</td>
            </tr>
            <tr>
                <th>Status</th>
                <td id="documentStatus">Ładowanie...</td>
            </tr>
        </table>

        <div class="text-center mt-4">
            <button id="signDocumentBtn" class="btn btn-success">Podpisz dokument</button>
            <button id="downloadDocumentBtn" class="btn btn-primary">Pobierz</button>
        </div>
    </div>
</div>

<!-- Modal podpisywania dokumentu -->
<div id="signDocumentModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Podpisz dokument</h2>
        <form id="signDocumentForm">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="signature" class="form-label">Podpis</label>
                <textarea id="signature" name="signature" class="form-control" placeholder="Wpisz swój podpis" required></textarea>
            </div>

            <button type="submit" class="btn btn-success">Zatwierdź podpis</button>
        </form>
    </div>
</div>

<script src="/js/documents.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
