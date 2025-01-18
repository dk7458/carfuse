<?php
require __DIR__ . '../../includes/db_connect.php';
require __DIR__ . '../../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch available reports
$reportDir = '../../documents/reports';
$reports = array_diff(scandir($reportDir), ['.', '..']);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Raporty</h1>

        <?php if (count($reports) > 0): ?>
            <ul class="list-group mt-4">
                <?php foreach ($reports as $report): ?>
                    <li class="list-group-item">
                        <a href="<?php echo "$reportDir/$report"; ?>" download><?php echo $report; ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="alert alert-info text-center mt-4">
                Brak dostępnych raportów.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
