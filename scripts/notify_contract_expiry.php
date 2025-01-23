<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

try {
    $currentDate = date('Y-m-d');
    $expiryDate = date('Y-m-d', strtotime('+3 days')); // Notify 3 days before expiry

    // Fetch contracts nearing expiry
    $stmt = $conn->prepare("
        SELECT b.id, u.name, f.make, f.model, b.dropoff_date
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN fleet f ON b.vehicle_id = f.id
        WHERE b.dropoff_date = ? AND b.status = 'active'
    ");
    $stmt->bind_param("s", $expiryDate);
    $stmt->execute();
    $contracts = $stmt->get_result();

    if ($contracts->num_rows > 0) {
        while ($contract = $contracts->fetch_assoc()) {
            $message = "
                Uwaga! Umowa rezerwacji (ID: {$contract['id']}) dla użytkownika {$contract['name']} 
                pojazdu {$contract['make']} {$contract['model']} wygaśnie w dniu {$contract['dropoff_date']}.
            ";

            // Notify admins
            $admins = $conn->query("SELECT email FROM users WHERE role = 'admin'");
            while ($admin = $admins->fetch_assoc()) {
                sendEmail($admin['email'], "Zbliżający się koniec umowy", $message);
            }

            echo "Expiry notification sent for contract ID: {$contract['id']}.\n";
        }
    } else {
        echo "No contracts nearing expiry for {$expiryDate}.\n";
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo "An error occurred while notifying contract expiry.\n";
}
?>
