$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
/**
 * File Path: /controllers/booking_controller.php
 * Description: Manages booking operations including availability checks, booking creation, and fetching user bookings.
 * Changelog:
 * - Added CSRF token validation for booking creation.
 * - Improved error handling and validation.
 * - Added logging for booking actions.
 * - Added support for booking cancellation.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';

require_once BASE_PATH . 'includes/pdf_generator.php';

require_once BASE_PATH . 'includes/user_queries.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/contract_generator.php'; // Include the contract generator

header('Content-Type: application/json');

try {
    $userId = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'check_availability':
                $pickupDate = $_POST['pickup_date'];
                $dropoffDate = $_POST['dropoff_date'];

                if (empty($pickupDate) || empty($dropoffDate)) {
                    throw new Exception("Pickup and drop-off dates are required.");
                }

                if (strtotime($pickupDate) >= strtotime($dropoffDate)) {
                    throw new Exception("Drop-off date must be after the pickup date.");
                }

                $query = "
                    SELECT f.id, f.make, f.model, f.year, f.price_per_day, f.image_path,
                           CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END AS has_promo
                    FROM fleet f
                    LEFT JOIN promotions p ON f.id = p.car_id AND p.start_date <= ? AND p.end_date >= ?
                    WHERE f.id NOT IN (
                        SELECT b.vehicle_id
                        FROM bookings b
                        WHERE NOT (
                            b.dropoff_date <= ? OR b.pickup_date >= ?
                        )
                    )
                ";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssss', $pickupDate, $dropoffDate, $pickupDate, $dropoffDate);
                $stmt->execute();
                $result = $stmt->get_result();

                $cars = [];
                while ($row = $result->fetch_assoc()) {
                    $cars[] = [
                        'id' => $row['id'],
                        'make' => $row['make'],
                        'model' => $row['model'],
                        'year' => $row['year'],
                        'price_per_day' => $row['price_per_day'],
                        'image_path' => $row['image_path'],
                        'has_promo' => (bool)$row['has_promo']
                    ];
                }

                echo json_encode(['success' => true, 'cars' => $cars]);
                break;

            case 'create_booking':
                if (!verifyCsrfToken($_POST['csrf_token'])) {
                    throw new Exception("Invalid CSRF token.");
                }

                $vehicleId = intval($_POST['vehicle_id']);
                $pickupDate = $_POST['pickup_date'];
                $dropoffDate = $_POST['dropoff_date'];
                $totalPrice = floatval($_POST['total_price']);
                $agreeTnC = $_POST['agree_tnc'] ?? '';
                $agreeContract = $_POST['agree_contract'] ?? '';

                if ($agreeTnC !== 'yes' || $agreeContract !== 'yes') {
                    throw new Exception("You must agree to the Terms & Conditions and confirm signing the contract.");
                }

                if (strtotime($pickupDate) >= strtotime($dropoffDate)) {
                    throw new Exception("Pickup date must be earlier than drop-off date.");
                }

                $stmt = $conn->prepare("
                    INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, total_price, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("iissi", $userId, $vehicleId, $pickupDate, $dropoffDate, $totalPrice);

                if (!$stmt->execute()) {
                    throw new Exception("Failed to create booking.");
                }

                $bookingId = $stmt->insert_id;

                // Generate contract and send email
                generateAndSendContract($conn, $userId, $vehicleId, $bookingId, $pickupDate, $dropoffDate, $totalPrice);

                logAction($conn, $userId, 'create_booking', "Booking ID: $bookingId");
                echo json_encode(['success' => true, 'message' => "Booking created successfully."]);
                break;

            case 'cancel_booking':
                $bookingId = intval($_POST['booking_id']);
                if ($bookingId === 0) {
                    throw new Exception("Invalid booking ID.");
                }

                $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $bookingId, $userId);

                if ($stmt->execute()) {
                    logAction($conn, $userId, 'cancel_booking', "Booking ID: $bookingId");
                    echo json_encode(['success' => true, 'message' => "Booking cancelled successfully."]);
                } else {
                    throw new Exception("Failed to cancel booking.");
                }
                break;

            default:
                throw new Exception("Unknown action.");
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'fetch_bookings':
                $stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();

                $bookings = [];
                while ($row = $result->fetch_assoc()) {
                    $bookings[] = $row;
                }

                echo json_encode(['success' => true, 'bookings' => $bookings]);
                break;

            case 'calendar_data':
                $query = "
                    SELECT 
                        f.make, f.model, 
                        b.pickup_date, b.dropoff_date 
                    FROM bookings b 
                    JOIN fleet f ON b.vehicle_id = f.id
                ";
                $result = $conn->query($query);

                $events = [];
                while ($row = $result->fetch_assoc()) {
                    $events[] = [
                        'title' => $row['make'] . ' ' . $row['model'],
                        'start' => $row['pickup_date'],
                        'end' => date('Y-m-d', strtotime($row['dropoff_date'] . ' +1 day'))
                    ];
                }

                echo json_encode($events);
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
    exit;
}
?>
