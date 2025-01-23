<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

echo "Starting Booking Flow Test...\n";

try {
    // Step 1: Login User
    $userId = 1; // Test user ID
    $_SESSION['user_id'] = $userId;
    echo "User logged in with ID: $userId\n";

    // Step 2: Select Vehicle
    $vehicleId = $conn->query("SELECT id FROM fleet WHERE availability = 1 LIMIT 1")->fetch_assoc()['id'];
    if (!$vehicleId) {
        throw new Exception("No available vehicles found.");
    }
    echo "Selected vehicle with ID: $vehicleId\n";

    // Step 3: Create Booking
    $pickupDate = date('Y-m-d', strtotime('+3 days'));
    $dropoffDate = date('Y-m-d', strtotime('+7 days'));
    $totalPrice = 500.00;

    $stmt = $conn->prepare("
        INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, total_price, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iissd", $userId, $vehicleId, $pickupDate, $dropoffDate, $totalPrice);

    if (!$stmt->execute()) {
        throw new Exception("Failed to create booking: " . $stmt->error);
    }
    $bookingId = $stmt->insert_id;
    echo "Booking created with ID: $bookingId\n";

    // Step 4: Check Contract Generation
    $contractPath = "../documents/contracts/contract_$bookingId.pdf";
    if (!file_exists($contractPath)) {
        throw new Exception("Contract was not generated.");
    }
    echo "Contract successfully generated at $contractPath\n";

    // Step 5: Validate Booking in Database
    $booking = $conn->query("SELECT * FROM bookings WHERE id = $bookingId")->fetch_assoc();
    if (!$booking) {
        throw new Exception("Booking not found in database.");
    }
    echo "Booking successfully validated in database.\n";

    echo "Booking Flow Test Completed Successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
