<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pdf_generator.php';
require_once __DIR__ . '/../includes/user_queries.php';
require_once __DIR__ . '/../includes/session_middleware.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

try {
    // Handle car availability check
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_availability') {
        $pickupDate = $_POST['pickup_date'];
        $dropoffDate = $_POST['dropoff_date'];

        // Validate dates
        if (empty($pickupDate) || empty($dropoffDate)) {
            throw new Exception("Both pickup and drop-off dates are required.");
        }

        if (strtotime($pickupDate) >= strtotime($dropoffDate)) {
            throw new Exception("Drop-off date must be after the pickup date.");
        }

        // Fetch available cars
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
        exit;
    }

    // Handle booking creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token.");
        }

        $userId = $_SESSION['user_id'];
        $vehicleId = intval($_POST['vehicle_id']);
        $pickupDate = $_POST['pickup_date'];
        $dropoffDate = $_POST['dropoff_date'];
        $totalPrice = floatval($_POST['total_price']);

        // Validate dates
        if (strtotime($pickupDate) >= strtotime($dropoffDate)) {
            throw new Exception("Pickup date must be earlier than drop-off date.");
        }

        // Insert booking into the database
        $stmt = $conn->prepare(
            "INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, total_price, created_at) VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("iissi", $userId, $vehicleId, $pickupDate, $dropoffDate, $totalPrice);

        if (!$stmt->execute()) {
            throw new Exception("Failed to create booking. Please try again later.");
        }

        $bookingId = $stmt->insert_id;

        // Fetch details for the contract
        $booking = [
            'pickup_date' => $pickupDate,
            'dropoff_date' => $dropoffDate,
            'total_price' => $totalPrice
        ];
        $vehicle = $conn->query("SELECT * FROM fleet WHERE id = $vehicleId")->fetch_assoc();
        $customer = getUserDetails($conn, $userId);

        // Generate the rental contract
        $htmlContent = generateContractHTML($booking, $vehicle, $customer);
        $outputPath = __DIR__ . "/../../users/user$userId/documents/contract_$bookingId.pdf";
        $signaturePath = __DIR__ . "/../../assets/images/signature.png";
        generatePDF($htmlContent, $outputPath, $signaturePath);

        // Save contract path to the database
        $stmt = $conn->prepare("UPDATE bookings SET rental_contract_pdf = ? WHERE id = ?");
        $stmt->bind_param("si", $outputPath, $bookingId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to save contract for the booking.");
        }

        // Notify user via email
        $contractLink = "https://carfuse.pl/users/user$userId/documents/contract_$bookingId.pdf";
        $emailContent = getBookingConfirmationEmail($customer['name'], $bookingId, $contractLink);
        if (!sendEmail($customer['email'], "Booking Confirmation", $emailContent)) {
            throw new Exception("Failed to send confirmation email.");
        }

        // Log the booking creation
        logAction($conn, $userId, 'create_booking', "Booking ID: $bookingId");

        echo json_encode(['success' => true, 'message' => "Booking created successfully."]);
        exit;
    }

    // Future extensions for update and cancel actions

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

?>
