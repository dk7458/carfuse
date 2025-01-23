$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

echo "Starting Notification Flow Test...\n";

try {
    // Step 1: Fetch Test Booking
    $bookingId = $conn->query("SELECT id FROM bookings WHERE status = 'active' LIMIT 1")->fetch_assoc()['id'];
    if (!$bookingId) {
        throw new Exception("No active bookings found.");
    }
    echo "Using booking with ID: $bookingId\n";

    // Step 2: Send User Notifications
    $booking = $conn->query("
        SELECT b.pickup_date, u.email, u.phone, u.name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.id = $bookingId
    ")->fetch_assoc();

    $userName = htmlspecialchars($booking['name']);
    $email = htmlspecialchars($booking['email']);
    $phone = htmlspecialchars($booking['phone']);
    $pickupDate = date('d-m-Y', strtotime($booking['pickup_date']));

    // Send Email Notification
    $emailMessage = "
        <h1>Przypomnienie o Rezerwacji</h1>
        <p>Drogi $userName,</p>
        <p>Przypominamy, że Twoja rezerwacja zaczyna się $pickupDate.</p>
    ";
    if (sendEmail($email, "Przypomnienie o Rezerwacji", $emailMessage)) {
        echo "Email notification sent successfully to $email\n";
    } else {
        throw new Exception("Failed to send email notification.");
    }

    // Send SMS Notification
    $smsMessage = "Przypomnienie: Twoja rezerwacja zaczyna się $pickupDate.";
    if (sendSMS($phone, $smsMessage)) {
        echo "SMS notification sent successfully to $phone\n";
    } else {
        throw new Exception("Failed to send SMS notification.");
    }

    // Step 3: Trigger MQTT Notification
    $mqttMessage = "Przypomnienie: Rezerwacja $bookingId zaczyna się $pickupDate.";
    sendMQTTNotification("admin/notifications", $mqttMessage);
    echo "MQTT notification published successfully.\n";

    echo "Notification Flow Test Completed Successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
