<?php

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';

try {
    // Define current date
    $currentDate = date('Y-m-d');

    // Expire contracts
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = 'canceled' 
        WHERE dropoff_date < ? AND status = 'active'
    ");
    $stmt->bind_param("s", $currentDate);

    if ($stmt->execute()) {
        echo "Contracts expired successfully.\n";
    } else {
        throw new Exception("Failed to update contracts: " . $stmt->error);
    }

    // Optional: Notify admins
    $affectedRows = $stmt->affected_rows;
    if ($affectedRows > 0) {
        $admins = $conn->query("SELECT email FROM users WHERE role = 'admin'");
        while ($admin = $admins->fetch_assoc()) {
            sendEmail($admin['email'], "Contracts Expired", "$affectedRows contracts have been expired automatically.");
        }
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo "An error occurred while processing contracts.\n";
}
?>
