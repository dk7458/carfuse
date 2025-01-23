<?php
// File Path: /views/admin/dynamic_pricing_manager.php
// Description: Admin interface for creating, editing, and logging dynamic pricing rules.
// Changelog:
// - Initial creation of the dynamic pricing manager interface.
// - Added functionality to view, edit, and delete existing pricing rules.

require_once __DIR__ . '/../../includes/session_middleware.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';

enforceRole(['admin', 'super_admin']);

// Fetch existing pricing rules
$rulesResult = $conn->query("SELECT * FROM pricing_rules");
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menadżer Dynamicznego Cennika</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <h1>Menadżer Dynamicznego Cennika</h1>

        <!-- Form for creating/editing pricing rules -->
        <form id="pricingRuleForm" class="row g-3 mt-4">
            <input type="hidden" name="rule_id" id="rule_id">
            <div class="col-md-4">
                <label for="rule_name" class="form-label">Nazwa Reguły:</label>
                <input type="text" name="rule_name" id="rule_name" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="criteria" class="form-label">Kryteria:</label>
                <input type="text" name="criteria" id="criteria" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="adjustment" class="form-label">Dostosowanie (%):</label>
                <input type="number" name="adjustment" id="adjustment" class="form-control" step="0.01" required>
            </div>
            <div class="col-md-2 mt-4">
                <button type="submit" class="btn btn-primary w-100">Zapisz</button>
            </div>
        </form>

        <!-- Existing pricing rules -->
        <div class="mt-5">
            <h3>Istniejące Reguły Cennika</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nazwa Reguły</th>
                        <th>Kryteria</th>
                        <th>Dostosowanie (%)</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rulesResult->num_rows > 0): ?>
                        <?php while ($rule = $rulesResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($rule['id']) ?></td>
                                <td><?= htmlspecialchars($rule['rule_name']) ?></td>
                                <td><?= htmlspecialchars($rule['criteria']) ?></td>
                                <td><?= htmlspecialchars($rule['adjustment']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-rule" data-id="<?= $rule['id'] ?>" data-name="<?= htmlspecialchars($rule['rule_name']) ?>" data-criteria="<?= htmlspecialchars($rule['criteria']) ?>" data-adjustment="<?= htmlspecialchars($rule['adjustment']) ?>">Edytuj</button>
                                    <button class="btn btn-sm btn-danger delete-rule" data-id="<?= $rule['id'] ?>">Usuń</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Brak reguł cennika.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="/assets/js/dynamic_pricing_manager.js"></script>
</body>
</html>
