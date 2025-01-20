<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pdf_generator.php';
require_once __DIR__ . '/../includes/user_queries.php';

session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

try {
    // Handle booking creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Nieprawidłowy token CSRF.");
        }

        $userId = $_SESSION['user_id'];
        $vehicleId = intval($_POST['vehicle_id']);
        $pickupDate = $_POST['pickup_date'];
        $dropoffDate = $_POST['dropoff_date'];
        $totalPrice = floatval($_POST['total_price']);

        // Validate dates
        if (strtotime($pickupDate) >= strtotime($dropoffDate)) {
            throw new Exception("Data odbioru musi być wcześniejsza niż data zwrotu.");
        }

        // Insert booking into the database
        $stmt = $conn->prepare(
            "INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, total_price, created_at) VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("iissi", $userId, $vehicleId, $pickupDate, $dropoffDate, $totalPrice);

        if (!$stmt->execute()) {
            throw new Exception("Nie udało się utworzyć rezerwacji. Spróbuj ponownie później.");
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
        $outputPath = "/home/u122931475/domains/carfuse.pl/public_html//users/user$userId/documents/contract_$bookingId.pdf";
        $signaturePath = "/home/u122931475/domains/carfuse.pl/public_html/assets/images/signature.png";
        generatePDF($htmlContent, $outputPath, $signaturePath);

        // Save contract path to the database
        $stmt = $conn->prepare("UPDATE bookings SET rental_contract_pdf = ? WHERE id = ?");
        $stmt->bind_param("si", $outputPath, $bookingId);
        if (!$stmt->execute()) {
            throw new Exception("Nie udało się zapisać umowy do rezerwacji.");
        }

        // Notify user via email
        $contractLink = "https://carfuse.pl/documents/contracts/contract_{$bookingId}.pdf";
        $emailContent = getBookingConfirmationEmail($customer['name'], $bookingId, $contractLink);
        if (!sendEmail($customer['email'], "Potwierdzenie Rezerwacji", $emailContent)) {
            throw new Exception("Nie udało się wysłać potwierdzenia e-mail.");
        }

        // Log the booking creation
        logUserAction($conn, $userId, 'create_booking', "Booking ID: $bookingId");

        // Redirect to booking success page
        redirect("/public/booking_success.php?booking_id=$bookingId");
    }

    // Future extensions for update and cancel actions

} catch (Exception $e) {
    // Log the error
    logError($e->getMessage());

    // Redirect to an error page or show a message
    redirect("/public/error.php?message=" . urlencode($e->getMessage()));
}
?>

