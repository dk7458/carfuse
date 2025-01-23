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

// Fetch signatures
$signatures = $conn->query("SELECT * FROM digital_signatures ORDER BY created_at DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM digital_signatures WHERE id = ?");
    $stmt->bind_param("i", $deleteId);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Podpis został usunięty pomyślnie.";
        redirect('/views/admin/signature_management.php');
    } else {
        $_SESSION['error_message'] = "Nie udało się usunąć podpisu.";
    }
}
?>

<div class="container mt-5">
    <h1 class="text-center">Zarządzanie Podpisami Cyfrowymi</h1>

    <?php include '../../views/shared/messages.php'; ?>

    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>ID</th>
                <th>Podpis</th>
                <th>Data Utworzenia</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($signature = $signatures->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $signature['id']; ?></td>
                    <td><img src="/documents/signatures/<?php echo $signature['file_path']; ?>" alt="Podpis" style="width: 150px;"></td>
                    <td><?php echo date('d-m-Y H:i:s', strtotime($signature['created_at'])); ?></td>
                    <td>
                        <form method="POST" class="standard-form" style="display: inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $signature['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć ten podpis?');">Usuń</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
