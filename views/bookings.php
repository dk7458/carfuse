<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . '../includes/user_queries.php';

require_once BASE_PATH . '../includes/functions.php';


// Fetch user bookings
$userId = $_SESSION['user_id'];
$bookings = getUserBookings($conn, $userId);
?>

<div class="container">
    <h2 class="mt-5">Twoje Rezerwacje</h2>
    <p class="text-center">Zarządzaj swoimi rezerwacjami poniżej.</p>

    <?php if ($bookings->num_rows > 0): ?>
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>ID Rezerwacji</th>
                    <th>Pojazd</th>
                    <th>Numer Rejestracyjny</th>
                    <th>Data Odbioru</th>
                    <th>Data Zwrotu</th>
                    <th>Cena Całkowita</th>
                    <th>Status</th>
                    <th>Umowa</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($booking = $bookings->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $booking['id']; ?></td>
                        <td><?php echo htmlspecialchars("{$booking['make']} {$booking['model']}"); ?></td>
                        <td><?php echo htmlspecialchars($booking['registration_number']); ?></td>
                        <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($booking['pickup_date']))); ?></td>
                        <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($booking['dropoff_date']))); ?></td>
                        <td><?php echo htmlspecialchars(number_format($booking['total_price'], 2, ',', ' ')); ?> PLN</td>
                        <td><?php echo htmlspecialchars(ucfirst($booking['status'])); ?></td>
                        <td>
                            <?php if ($booking['rental_contract_pdf']): ?>
                                <a href="<?php echo htmlspecialchars($booking['rental_contract_pdf']); ?>" target="_blank">Pobierz</a>
                            <?php else: ?>
                                Niedostępna
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($booking['status'] === 'active'): ?>
                                <form method="POST" action="/controllers/booking_controller.php" class="d-inline ajax-form">
                                    <input type="hidden" name="action" value="cancel_booking">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Anuluj</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">
            Nie masz żadnych aktywnych rezerwacji.
        </div>
    <?php endif; ?>
</div>

