<?php
require_once __DIR__ . '/../../../helpers/SecurityHelper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}
?>

<h1 class="text-center">Zarządzanie Dokumentami</h1>

<div class="documents-container">
    <form id="documentUploadForm" class="mt-3">
        <?= csrf_field() ?>
        <input type="file" name="document" class="form-control">
        <button type="submit" class="btn btn-primary mt-2">Prześlij Dokument</button>
    </form>

    <ul id="documentList" class="list-group mt-3">
        <!-- Dokumenty będą ładowane dynamicznie -->
    </ul>
</div>

<script src="/js/documents.js"></script>
