$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch all admins
$admins = $conn->query("SELECT id, name, email FROM users WHERE role = 'admin'");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $deleteId);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Administrator został usunięty pomyślnie.";
        redirect('/views/admin/manage_admins.php');
    } else {
        $_SESSION['error_message'] = "Nie udało się usunąć administratora.";
    }
}
?>

<div class="container mt-5">
    <h1 class="text-center">Zarządzaj Administratorami</h1>

    <?php include '../../views/shared/messages.php'; ?>

    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imię</th>
                <th>E-mail</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($admin = $admins->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $admin['id']; ?></td>
                    <td><?php echo htmlspecialchars($admin['name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td>
                        <form method="POST" class="standard-form" style="display: inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $admin['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć tego administratora?');">Usuń</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
