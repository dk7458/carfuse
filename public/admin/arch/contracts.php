<?php
require '/home/u122931475/domains/carfuse.pl/public_html/../includes/db_connect.php';
require '/home/u122931475/domains/carfuse.pl/public_html/../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch contracts
$statusFilter = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : null;

$sql = "
    SELECT b.id, u.name AS user_name, f.make, f.model, b.pickup_date, b.dropoff_date, b.status 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN fleet f ON b.vehicle_id = f.id
";

if ($statusFilter) {
    $sql .= " WHERE b.status = '$statusFilter'";
}

$sql .= " ORDER BY b.pickup_date DESC";
$contracts = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Umowy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Umowy</h1>

        <form method="GET" class="standard-form row g-3 mt-4">
            <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Wszystkie</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Aktywne</option>
                    <option value="canceled" <?php echo $statusFilter === 'canceled' ? 'selected' : ''; ?>>Anulowane</option>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtruj</button>
            </div>
        </form>

        <?php if ($contracts->num_rows > 0): ?>
            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Użytkownik</th>
                        <th>Pojazd</th>
                        <th>Data odbioru</th>
                        <th>Data zwrotu</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($contract = $contracts->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $contract['id']; ?></td>
                            <td><?php echo htmlspecialchars($contract['user_name']); ?></td>
                            <td><?php echo "{$contract['make']} {$contract['model']}"; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($contract['pickup_date'])); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($contract['dropoff_date'])); ?></td>
                            <td><?php echo ucfirst($contract['status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center mt-4">
                Brak umów do wyświetlenia.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
