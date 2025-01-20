<?php
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

try {
    $currentDate = date('Y-m-d');
    $reminderDate = date('Y-m-d', strtotime('+1 day')); // Remind 1 day before pickup

    // Fetch bookings requiring reminders
    $stmt = $conn->prepare("
        SELECT b.id, u.name, u.email, u.phone, f.make, f.model, b.pickup_date
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN fleet f ON b.vehicle_id = f.id
        WHERE b.pickup_date = ? AND b.status = 'active'
    ");
    $stmt->bind_param("s", $reminderDate);
    $stmt->execute();
    $bookings = $stmt->get_result();

    if ($bookings->num_rows > 0) {
        while ($booking = $bookings->fetch_assoc()) {
            $message = "
                Dzień dobry, {$booking['name']}! 
                Przypominamy, że rezerwacja pojazdu {$booking['make']} {$booking['model']} 
                rozpoczyna się {$booking['pickup_date']}. 
                Życzymy miłej podróży!
            ";

            // Send email
            sendEmail($booking['email'], "Przypomnienie o rezerwacji", $message);

            // Send SMS (if phone number exists)
            if (!empty($booking['phone'])) {
                sendSMS($booking['phone'], $message);
            }

            echo "Reminder sent for booking ID: {$booking['id']}\n";
        }
    } else {
        echo "No bookings requiring reminders for {$reminderDate}.\n";
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo "An error occurred while sending booking reminders.\n";
}
?>
