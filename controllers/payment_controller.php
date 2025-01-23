$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';

require_once BASE_PATH . 'includes/session_middleware.php';


header('Content-Type: application/json');

try {
    $userId = $_SESSION['user_id'] ?? null;

    // Ensure the user is logged in
    if (!$userId) {
        http_response_code(403);
        throw new Exception("Access denied. Please log in.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'process_payment':
                // Validate CSRF token
                if (!verifyCsrfToken($_POST['csrf_token'])) {
                    throw new Exception("Invalid CSRF token.");
                }

                $bookingId = intval($_POST['booking_id']);
                $paymentMethod = $_POST['payment_method'] ?? null;

                if (!$bookingId || !$paymentMethod) {
                    throw new Exception("Missing payment details.");
                }

                // Fetch booking details
                $stmt = $conn->prepare("
                    SELECT total_price, status 
                    FROM bookings 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->bind_param("ii", $bookingId, $userId);
                $stmt->execute();
                $booking = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$booking) {
                    throw new Exception("Booking not found.");
                }

                if ($booking['status'] !== 'active') {
                    throw new Exception("This booking cannot be paid. Please contact support.");
                }

                $totalPrice = $booking['total_price'];

                // Simulate payment processing (e.g., integrate with payment gateway)
                $paymentSuccess = true; // Assume payment succeeds

                if ($paymentSuccess) {
                    // Update booking status to 'paid'
                    $stmt = $conn->prepare("
                        UPDATE bookings 
                        SET status = 'paid' 
                        WHERE id = ?
                    ");
                    $stmt->bind_param("i", $bookingId);
                    $stmt->execute();

                    // Log payment action
                    logAction($conn, $userId, 'process_payment', "Booking ID: $bookingId, Payment: $totalPrice PLN");

                    echo json_encode(['success' => true, 'message' => "Payment processed successfully."]);
                } else {
                    throw new Exception("Payment failed. Please try again.");
                }
                break;

            case 'refund':
                // Validate admin access
                if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
                    throw new Exception("Unauthorized access. Refunds can only be processed by admins.");
                }

                $bookingId = intval($_POST['booking_id']);
                $refundAmount = floatval($_POST['refund_amount']);

                if (!$bookingId || !$refundAmount) {
                    throw new Exception("Invalid refund details.");
                }

                // Fetch booking details
                $stmt = $conn->prepare("
                    SELECT status, total_price 
                    FROM bookings 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $bookingId);
                $stmt->execute();
                $booking = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$booking || $booking['status'] !== 'paid') {
                    throw new Exception("Refund can only be processed for paid bookings.");
                }

                // Ensure refund amount is valid
                if ($refundAmount > $booking['total_price']) {
                    throw new Exception("Refund amount exceeds the total booking price.");
                }

                // Process refund (e.g., refund via payment gateway)
                $refundSuccess = true; // Assume refund succeeds

                if ($refundSuccess) {
                    // Log the refund
                    $stmt = $conn->prepare("
                        INSERT INTO refund_logs (booking_id, refunded_amount) 
                        VALUES (?, ?)
                    ");
                    $stmt->bind_param("id", $bookingId, $refundAmount);
                    $stmt->execute();

                    // Update booking status if fully refunded
                    if ($refundAmount === $booking['total_price']) {
                        $stmt = $conn->prepare("
                            UPDATE bookings 
                            SET status = 'canceled', refund_status = 'processed' 
                            WHERE id = ?
                        ");
                        $stmt->bind_param("i", $bookingId);
                        $stmt->execute();
                    } else {
                        $stmt = $conn->prepare("
                            UPDATE bookings 
                            SET refund_status = 'processed' 
                            WHERE id = ?
                        ");
                        $stmt->bind_param("i", $bookingId);
                        $stmt->execute();
                    }

                    logAction($conn, $_SESSION['user_id'], 'refund', "Booking ID: $bookingId, Refund: $refundAmount PLN");

                    echo json_encode(['success' => true, 'message' => "Refund processed successfully."]);
                } else {
                    throw new Exception("Refund failed. Please try again.");
                }
                break;

            default:
                throw new Exception("Unknown action.");
        }
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    logError($e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
