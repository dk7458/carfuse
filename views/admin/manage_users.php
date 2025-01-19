<?php
require '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $deleteId = intval($_POST['delete_id']);
        $conn->query("DELETE FROM users WHERE id = $deleteId");
        $_SESSION['success_message'] = "Użytkownik został usunięty.";
        redirect('/views/admin/manage_users.php');
    }
}
?>

<div class="container mt-5">
    <h1 class="text-center">Zarządzanie Użytkownikami</h1>

    <?php include '/home/u122931475/domains/carfuse.pl/public_html/views/shared/messages.php'; ?>

    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imię</th>
                <th>Nazwisko</th>
                <th>Email</th>
                <th>Data Utworzenia</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['surname']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo date('d-m-Y H:i:s', strtotime($user['created_at'])); ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć tego użytkownika?');">Usuń</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
