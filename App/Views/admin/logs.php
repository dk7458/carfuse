<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-center">Dzienniki systemowe</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Dzienniki systemowe</h3>
    </div>

    <!-- Filtry logów -->
    <form id="logFilterForm" class="row mb-4">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <select class="form-control" name="log_type">
                <option value="">Wybierz typ logu</option>
                <option value="error">Błąd</option>
                <option value="info">Informacja</option>
                <option value="warning">Ostrzeżenie</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" name="start_date" placeholder="Data początkowa">
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" name="end_date" placeholder="Data końcowa">
        </div>
        <div class="col-md-3 text-end">
            <button type="submit" class="btn btn-primary">Filtruj</button>
        </div>
    </form>

    <!-- Tabela logów -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Data i czas</th>
                <th>Typ logu</th>
                <th>Wiadomość</th>
                <th>Szczegóły</th>
            </tr>
        </thead>
        <tbody id="systemLogs">
            <!-- Dane logów będą ładowane dynamicznie -->
        </tbody>
    </table>
</div>

<script src="/js/admin.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
