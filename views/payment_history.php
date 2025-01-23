<?php

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

// Fetch user payment history
$userId = $_SESSION['user_id'];
$payments = $conn->query("SELECT id, payment_method, amount, currency, status, created_at FROM payments WHERE user_id = $userId ORDER BY created_at DESC");
?>

<div class="container">
    <h2 class="mt-5">Historia Płatności</h2>
    <p class="text-center">Poniżej znajduje się lista Twoich płatności:</p>

    <?php if ($payments->num_rows > 0): ?>
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>ID Płatności</th>
                    <th>Metoda Płatności</th>
                    <th>Kwota</th>
                    <th>Waluta</th>
                    <th>Status</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($payment = $payments->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($payment['id']); ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                        <td><?php echo number_format($payment['amount'], 2, ',', ' '); ?></td>
                        <td><?php echo htmlspecialchars($payment['currency']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($payment['status'])); ?></td>
                        <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($payment['created_at']))); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">
            Brak zapisanych płatności.
        </div>
    <?php endif; ?>
</div>

