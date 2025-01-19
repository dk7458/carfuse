<?php
require '../../includes/db_connect.php';
require '../../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch notifications
$typeFilter = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : null;
$dateFilter = isset($_GET['date']) ? htmlspecialchars($_GET['date']) : null;

$sql = "SELECT n.id, u.name AS user_name, n.type, n.message, n.sent_at 
        FROM notifications n 
        JOIN users u ON n.user_id = u.id 
        WHERE 1 = 1";

if ($typeFilter) {
    $sql .= " AND n.type = '$typeFilter'";
}

if ($dateFilter) {
    $sql .= " AND DATE(n.sent_at) = '$dateFilter'";
}

$sql .= " ORDER BY n.sent_at DESC";
$notifications = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Powiadomienia - Panel Administratora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">

</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Zarządzanie Powiadomieniami</h1>

        <form method="GET" class="standard-form row g-3 mt-4">
            <div class="col-md-4">
                <label for="type" class="form-label">Typ Powiadomienia</label>
                <select id="type" name="type" class="form-select">
                    <option value="" selected>Wszystkie</option>
                    <option value="email" <?php echo $typeFilter === 'email' ? 'selected' : ''; ?>>E-mail</option>
                    <option value="sms" <?php echo $typeFilter === 'sms' ? 'selected' : ''; ?>>SMS</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="date" class="form-label">Data Wysłania</label>
                <input type="date" id="date" name="date" class="form-control" value="<?php echo $dateFilter; ?>">
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtruj</button>
            </div>
        </form>

        <?php if ($notifications->num_rows > 0): ?>
            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Użytkownik</th>
                        <th>Typ</th>
                        <th>Treść</th>
                        <th>Data Wysłania</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($notification = $notifications->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $notification['id']; ?></td>
                            <td><?php echo htmlspecialchars($notification['user_name']); ?></td>
                            <td><?php echo strtoupper($notification['type']); ?></td>
                            <td><?php echo htmlspecialchars($notification['message']); ?></td>
                            <td><?php echo date('d-m-Y H:i:s', strtotime($notification['sent_at'])); ?></td>
                            <td>
                                <a href="/controllers/admin_notification_controller.php?action=resend&id=<?php echo $notification['id']; ?>" 
                                   class="btn btn-warning btn-sm">Wyślij ponownie</a>
                                <a href="/controllers/admin_notification_controller.php?action=delete&id=<?php echo $notification['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Czy na pewno chcesz usunąć to powiadomienie?');">Usuń</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center mt-4">
                Brak powiadomień do wyświetlenia.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
