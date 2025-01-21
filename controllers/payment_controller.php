<?php
// File Path: /controllers/payment_controller.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session_middleware.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
        // Validate CSRF token
        if (!validateCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Nieprawidłowy token CSRF.");
        }

        $userId = $_SESSION['user_id'];
        $bookingId = intval($_POST['booking_id']);
        $paymentMethod = $_POST['payment_method'];

        if (!$bookingId || !$paymentMethod) {
            throw new Exception("Nieprawidłowe dane płatności.");
        }

        // Fetch booking details
        $stmt = $conn->prepare("SELECT total_price FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $bookingId, $userId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            throw new Exception("Nie znaleziono rezerwacji.");
        }

        $totalPrice = $booking['total_price'];

        // Prepare payment data (mock example for integration)
        $paymentData = [
            'booking_id' => $bookingId,
            'user_id' => $userId,
            'amount' => $totalPrice,
            'method' => $paymentMethod
        ];

        // Redirect to payment provider (example: generate a URL)
        $paymentProviderUrl = "https://paymentprovider.com/pay?" . http_build_query([
            'amount' => $totalPrice,
            'method' => $paymentMethod,
            'callback_url' => "https://yourdomain.com/controllers/payment_status.php?booking_id=$bookingId"
        ]);

        // Log payment initiation
        logAction($userId, 'initiate_payment', json_encode($paymentData));

        echo json_encode(['success' => true, 'redirect_url' => $paymentProviderUrl]);
        exit;
    }

    throw new Exception("Nieprawidłowe żądanie.");

} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
