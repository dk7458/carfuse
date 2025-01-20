<?php

require once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/pdf_generator.php';

session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

// Create a new booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Nieprawidłowy token CSRF.");
    }

    $userId = $_SESSION['user_id'];
    $vehicleId = intval($_POST['vehicle_id']);
    $pickupDate = $_POST['pickup_date'];
    $dropoffDate = $_POST['dropoff_date'];
    $totalPrice = floatval($_POST['total_price']);

    // Insert booking into the database
    $sql = "INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, total_price, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissi", $userId, $vehicleId, $pickupDate, $dropoffDate, $totalPrice);

    if ($stmt->execute()) {
        $bookingId = $stmt->insert_id;

        // Fetch details for the contract
        $booking = [
            'pickup_date' => $pickupDate,
            'dropoff_date' => $dropoffDate,
            'total_price' => $totalPrice
        ];
        $vehicle = $conn->query("SELECT * FROM fleet WHERE id = $vehicleId")->fetch_assoc();
        $customer = $conn->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();

        // Generate the rental contract
        $htmlContent = generateContractHTML($booking, $vehicle, $customer);
        $outputPath = "../documents/contracts/contract_{$bookingId}.pdf";
        $signaturePath = "../assets/images/signature.png";
        generatePDF($htmlContent, $outputPath, $signaturePath);

        // Save contract path to the database
        $conn->query("UPDATE bookings SET rental_contract_pdf = '$outputPath' WHERE id = $bookingId");

        // Notify user via email
        $contractLink = "https://yourdomain.com/documents/contracts/contract_{$bookingId}.pdf";
        $emailContent = getBookingConfirmationEmail($customer['name'], $bookingId, $contractLink);
        sendEmail($customer['email'], "Potwierdzenie Rezerwacji", $emailContent);

        // Log the booking creation
        logAction($userId, 'create_booking', "Booking ID: $bookingId");

        // Redirect to booking success page
        redirect("/public/booking_success.php?booking_id=$bookingId");
    } else {
        die("Nie udało się utworzyć rezerwacji. Spróbuj ponownie później.");
    }
}

// Handle booking updates (future extension)

// Handle booking cancellations (future extension)

?>
