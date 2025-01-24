<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/admin/contract_manager.php
require_once BASE_PATH . 'includes/session_middleware.php';
require_once BASE_PATH . 'functions/email.php';
require_once BASE_PATH . 'controllers/contract_ctrl.php';

enforceRole(['admin', 'super_admin']); // Allow only admins and super admins

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch data using the centralized proxy
$filters = [
    'search' => $_GET['search'] ?? '',
    'startDate' => $_GET['start_date'] ?? '',
    'endDate' => $_GET['end_date'] ?? ''
];
$queryString = http_build_query($filters);
$response = file_get_contents(BASE_URL . "/public/api.php?endpoint=contracts&action=fetch_contracts&" . $queryString);
$data = json_decode($response, true);

if ($data['success']) {
    $contracts = $data['contracts'];
    $totalContracts = $data['totalContracts'];
    $totalPages = ceil($totalContracts / 10);
} else {
    $contracts = [];
    $totalContracts = 0;
    $totalPages = 1;
}

$adminSignature = getAdminSignature();
$contractTemplates = getContractTemplates();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Umowami</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/contract_manager.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <h1>Zarządzanie Umowami</h1>

        <!-- Admin Signature Management -->
        <h2 class="mt-4">Podpis Administratora</h2>
        <div class="row">
            <div class="col-md-6">
                <?php if ($adminSignature): ?>
                    <p>Aktualny podpis:</p>
                    <img src="<?= htmlspecialchars($adminSignature) ?>" alt="Admin Signature" style="max-width: 100px;">
                <?php else: ?>
                    <p>Nie ustawiono podpisu administratora.</p>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data" action="/controllers/contract_ctrl.php">
                    <input type="hidden" name="action" value="upload_signature">
                    <div class="mb-3">
                        <label for="signature" class="form-label">Wgraj Podpis</label>
                        <input type="file" name="signature" id="signature" class="form-control" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Zapisz Podpis</button>
                </form>
            </div>
        </div>

        <!-- Contract Template Management -->
        <h2 class="mt-4">Szablony Umów</h2>
        <div class="row">
            <div class="col-md-6">
                <ul class="list-group">
                    <?php foreach ($contractTemplates as $template): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars(basename($template)) ?>
                            <a href="<?= htmlspecialchars($template) ?>" target="_blank" class="btn btn-sm btn-info">Podgląd</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" enctype="multipart/form-data" action="/controllers/contract_ctrl.php" class="mt-3">
                    <input type="hidden" name="action" value="upload_template">
                    <div class="mb-3">
                        <label for="template" class="form-label">Wgraj Szablon Umowy</label>
                        <input type="file" name="template" id="template" class="form-control" accept=".html,.pdf" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Dodaj Szablon</button>
                </form>
            </div>
        </div>

        <!-- Contract Management Section -->
        <h2 class="mt-4">Zarządzaj Umowami</h2>
        <table class="table mt-4 table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Klient</th>
                    <th>Pojazd</th>
                    <th>Data Utworzenia</th>
                    <th>Plik</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($contracts) > 0): ?>
                    <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td><?= $contract['id'] ?></td>
                            <td><?= htmlspecialchars($contract['user_name']) ?></td>
                            <td><?= htmlspecialchars($contract['vehicle']) ?></td>
                            <td><?= htmlspecialchars($contract['created_at']) ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($contract['file_path']) ?>" target="_blank" class="btn btn-sm btn-info">Pobierz</a>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger delete-contract" data-id="<?= $contract['id'] ?>">Usuń</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Brak umów w bazie danych.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <form method="POST" action="/public/api.php?endpoint=contracts&action=add_contract">
            <input type="text" name="contract_details" placeholder="Enter contract details">
            <button type="submit">Add Contract</button>
        </form>
    </div>

    <!-- Include JavaScript -->
    <script src="/assets/js/contract_manager.js"></script>
</body>
</html>
