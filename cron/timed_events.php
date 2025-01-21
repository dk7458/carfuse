<?php
// File Path: /cron/timed_events.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: text/plain; charset=UTF-8');

// Function to log cron job actions
function logCronAction($message) {
    echo date('[Y-m-d H:i:s] ') . $message . "\n";
}

try {
    // ==========================
    // Fetch and Process Pending Events
    // ==========================
    $currentDate = date('Y-m-d');
    $query = "
        SELECT te.id, te.event_type, te.reference_id, te.scheduled_date, f.make, f.model, f.registration_number, u.email 
        FROM timed_events te
        LEFT JOIN fleet f ON te.reference_id = f.id AND te.event_type IN ('maintenance_upcoming', 'maintenance_overdue')
        LEFT JOIN users u ON te.reference_id = u.id AND te.event_type = 'booking_reminder'
        WHERE te.status = 'pending' AND te.scheduled_date <= CURDATE()
    ";
    $events = $conn->query($query);

    while ($event = $events->fetch_assoc()) {
        $eventId = $event['id'];
        $eventType = $event['event_type'];
        $referenceId = $event['reference_id'];

        try {
            switch ($eventType) {
                case 'maintenance_overdue':
                    $message = "Vehicle {$event['make']} {$event['model']} ({$event['registration_number']}) is overdue for maintenance!";
                    foreach (fetchAdminEmails($conn) as $email) {
                        sendNotification('email', $email, 'Overdue Maintenance', $message);
                    }
                    logCronAction("Overdue maintenance email sent for Vehicle ID: $referenceId");
                    break;

                case 'maintenance_upcoming':
                    $message = "Vehicle {$event['make']} {$event['model']} ({$event['registration_number']}) is scheduled for maintenance on {$event['scheduled_date']}.";
                    foreach (fetchAdminEmails($conn) as $email) {
                        sendNotification('email', $email, 'Upcoming Maintenance', $message);
                    }
                    logCronAction("Upcoming maintenance email sent for Vehicle ID: $referenceId");
                    break;

                case 'booking_reminder':
                    if ($event['email']) {
                        $message = "Reminder: Your booking for {$event['make']} {$event['model']} is scheduled for pickup tomorrow!";
                        sendNotification('email', $event['email'], 'Booking Reminder', $message);
                        logCronAction("Booking reminder sent for Booking ID: $referenceId");
                    }
                    break;

                default:
                    logError("Unhandled event type: $eventType");
                    break;
            }

            // Mark event as processed
            $updateStmt = $conn->prepare("UPDATE timed_events SET status = 'processed', processed_at = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $eventId);
            $updateStmt->execute();

        } catch (Exception $e) {
            logError("Error processing event ID $eventId: " . $e->getMessage());
            logCronAction("Failed to process event ID $eventId: " . $e->getMessage());
        }
    }

    // ==========================
    // Expired Payment Cleanup
    // ==========================
    $paymentCleanupQuery = "
        DELETE FROM payment_methods
        WHERE is_default = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    $conn->query($paymentCleanupQuery);
    logCronAction("Expired non-default payment methods cleaned up.");

} catch (Exception $e) {
    logError("Cron Job Error: " . $e->getMessage());
    logCronAction("Error: " . $e->getMessage());
}

logCronAction("Cron job completed.");
?>
