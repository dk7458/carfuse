<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-center">Raporty administracyjne</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Generowanie raportów</h3>
    </div>

    <!-- Formularz generowania raportów -->
    <form id="adminReportForm" class="mt-4">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="reportType" class="form-label">Typ raportu</label>
            <select class="form-select" id="reportType" name="reportType" required>
                <option value="" disabled selected>Wybierz typ raportu</option>
                <option value="bookings">Rezerwacje</option>
                <option value="payments">Płatności</option>
                <option value="users">Użytkownicy</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="startDate" class="form-label">Data początkowa</label>
            <input type="date" class="form-control" id="startDate" name="startDate" required>
        </div>

        <div class="mb-3">
            <label for="endDate" class="form-label">Data końcowa</label>
            <input type="date" class="form-control" id="endDate" name="endDate" required>
        </div>

        <div class="mb-3">
            <label for="format" class="form-label">Format raportu</label>
            <select class="form-select" id="format" name="format" required>
                <option value="csv">CSV</option>
                <option value="pdf">PDF</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Generuj raport</button>
    </form>

    <div id="responseMessage" class="mt-3"></div>
</div>

<script src="/js/admin.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
