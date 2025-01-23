$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /controllers/payment_status.php
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/functions.php';

require_once BASE_PATH . 'includes/email.php';

require_once BASE_PATH . 'includes/notifications.php';


header('Content-Type: text/html; charset=UTF-8');

try {
    $bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : '';

    if (!$bookingId || empty($status)) {
        throw new Exception("Nieprawidłowe dane statusu płatności.");
    }

    // Fetch booking details
    $stmt = $conn->prepare("SELECT user_id, total_price, vehicle_id, pickup_date, dropoff_date FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        throw new Exception("Nie znaleziono rezerwacji.");
    }

    $userId = $booking['user_id'];
    $totalPrice = $booking['total_price'];
    $vehicle = getVehicleDetails($conn, $booking['vehicle_id']);
    $customer = getUserDetails($conn, $userId);

    if ($status === 'success') {
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $bookingId);
        if (!$stmt->execute()) {
            throw new Exception("Nie udało się zaktualizować statusu rezerwacji.");
        }
        $stmt->close();

        // Generate contract link
        $contractLink = "/users/user$userId/documents/contract_$bookingId.pdf";

        // Send booking confirmation email
        $emailData = [
            'name' => $customer['name'],
            'car' => "{$vehicle['make']} {$vehicle['model']}",
            'pickup_date' => $booking['pickup_date'],
            'dropoff_date' => $booking['dropoff_date'],
            'price' => $totalPrice,
            'contract_link' => $contractLink,
        ];
        $emailBody = applyTemplate('booking_confirmation.php', $emailData);
        if (!sendEmail($customer['email'], "Potwierdzenie Rezerwacji #$bookingId", $emailBody)) {
            logError("Failed to send booking confirmation email for booking ID $bookingId.");
        }

        // Send SMS notification
        if (!empty($customer['phone'])) {
            $smsMessage = "Dziękujemy za rezerwację! Twój samochód: {$vehicle['make']} {$vehicle['model']}. Odbiór: {$booking['pickup_date']}, zwrot: {$booking['dropoff_date']}. Cena: $totalPrice PLN.";
            sendSmsNotification($customer['phone'], $smsMessage);
        }

        // Log successful payment
        logAction($userId, 'payment_success', json_encode(["Booking ID" => $bookingId, "Amount" => $totalPrice]));

        // Redirect to success page
        header("Location: /views/user/payment_success.php?booking_id=$bookingId");
        exit();
    } elseif ($status === 'failed') {
        // Log failed payment
        logAction($userId, 'payment_failed', json_encode(["Booking ID" => $bookingId]));

        // Redirect to failure page
        header("Location: /views/user/payment_failed.php?booking_id=$bookingId");
        exit();
    } else {
        throw new Exception("Nieprawidłowy status płatności.");
    }
} catch (Exception $e) {
    logError($e->getMessage());
    echo "<h1>Wystąpił błąd</h1><p>{$e->getMessage()}</p>";
    exit;
}
?>
