<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

// Fetch user's payment methods
$userId = $_SESSION['user_id'];
$paymentMethods = $conn->query("SELECT id, method_name, details, is_default FROM payment_methods WHERE user_id = $userId ORDER BY is_default DESC, id ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $methodName = $_POST['method_name'] ?? '';
        $details = $_POST['details'] ?? '';

        if ($methodName && $details) {
            $stmt = $conn->prepare("INSERT INTO payment_methods (user_id, method_name, details) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $methodName, $details);

            if ($stmt->execute()) {
                $successMessage = "Metoda płatności została dodana pomyślnie.";
            } else {
                $errorMessage = "Wystąpił błąd podczas dodawania metody płatności.";
            }
        } else {
            $errorMessage = "Proszę wypełnić wszystkie pola.";
        }
    } elseif ($action === 'delete') {
        $methodId = $_POST['method_id'] ?? '';

        if ($methodId) {
            $stmt = $conn->prepare("DELETE FROM payment_methods WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $methodId, $userId);

            if ($stmt->execute()) {
                $successMessage = "Metoda płatności została usunięta.";
            } else {
                $errorMessage = "Wystąpił błąd podczas usuwania metody płatności.";
            }
        }
    } elseif ($action === 'set_default') {
        $methodId = $_POST['method_id'] ?? '';

        if ($methodId) {
            $conn->begin_transaction();

            try {
                $conn->query("UPDATE payment_methods SET is_default = 0 WHERE user_id = $userId");
                $stmt = $conn->prepare("UPDATE payment_methods SET is_default = 1 WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $methodId, $userId);
                $stmt->execute();

                $conn->commit();
                $successMessage = "Domyślna metoda płatności została zaktualizowana.";
            } catch (Exception $e) {
                $conn->rollback();
                $errorMessage = "Wystąpił błąd podczas aktualizacji domyślnej metody płatności.";
            }
        }
    }
}
?>

<div class="container">
    <h2 class="mt-5">Metody Płatności</h2>
    <p class="text-center">Zarządzaj swoimi metodami płatności poniżej:</p>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php elseif (isset($errorMessage)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <h3 class="mt-4">Twoje Metody Płatności</h3>
    <?php if ($paymentMethods->num_rows > 0): ?>
        <ul class="list-group">
            <?php while ($method = $paymentMethods->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?php echo htmlspecialchars($method['method_name'] . ' (' . $method['details'] . ')'); ?></span>
                    <div>
                        <?php if ($method['is_default']): ?>
                            <span class="badge bg-success">Domyślna</span>
                        <?php else: ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="set_default">
                                <input type="hidden" name="method_id" value="<?php echo htmlspecialchars($method['id']); ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Ustaw jako domyślną</button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="method_id" value="<?php echo htmlspecialchars($method['id']); ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Usuń</button>
                        </form>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">
            Brak zapisanych metod płatności.</div>
    <?php endif; ?>

    <h3 class="mt-5">Dodaj Nową Metodę Płatności</h3>
    <form method="POST" class="mt-4">
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
            <label for="method_name" class="form-label">Nazwa Metody (np. BLIK, Karta Kredytowa)</label>
            <input type="text" id="method_name" name="method_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="details" class="form-label">Szczegóły (np. numer telefonu, ostatnie 4 cyfry karty)</label>
            <input type="text" id="details" name="details" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Dodaj Metodę</button>
    </form>
</div>

