<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Moje raporty</h1>

<div class="reports-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Wygeneruj raport</h3>
        <form id="userReportForm" class="mt-4">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="reportType" class="form-label">Typ raportu</label>
                <select class="form-select" id="reportType" name="reportType" required>
                    <option value="" disabled selected>Wybierz typ raportu</option>
                    <option value="bookings">Moje rezerwacje</option>
                    <option value="payments">Moje płatności</option>
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
</div>

<script src="/js/user_reports.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
