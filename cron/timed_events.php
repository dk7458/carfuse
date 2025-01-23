<?php
/**
 * File Path: /cron/timed_events.php
 * Description: Processes scheduled events, including maintenance notifications, booking reminders, and expired payment cleanup.
 * Changelog:
 * - Improved error handling and modularity.
 * - Added configurable log directory.
 * - Enhanced query validation and execution safety.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


header('Content-Type: text/plain; charset=UTF-8');

// Set log directory and file
$logDirectory = __DIR__ . '/../logs/';
$logFile = $logDirectory . 'cron_timed_events.log';

// Ensure log directory exists
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0755, true);
}

// Function to log cron job actions
function logCronAction($message)
{
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s]');
    $formattedMessage = "$timestamp $message\n";
    echo $formattedMessage;
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

try {
    logCronAction("Cron job started.");

    // ==========================
    // Fetch and Process Pending Events
    // ==========================
    $currentDate = date('Y-m-d');
    $query = "
        SELECT 
            te.id, 
            te.event_type, 
            te.reference_id, 
            te.scheduled_date, 
            f.make, 
            f.model, 
            f.registration_number, 
            u.email 
        FROM timed_events te
        LEFT JOIN fleet f ON te.reference_id = f.id AND te.event_type IN ('maintenance_upcoming', 'maintenance_overdue')
        LEFT JOIN users u ON te.reference_id = u.id AND te.event_type = 'booking_reminder'
        WHERE te.status = 'pending' AND te.scheduled_date <= CURDATE()
    ";
    $events = $conn->query($query);

    if (!$events) {
        throw new Exception("Failed to fetch pending events: " . $conn->error);
    }

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
                    logCronAction("Overdue maintenance email sent for Vehicle ID: $referenceId.");
                    break;

                case 'maintenance_upcoming':
                    $message = "Vehicle {$event['make']} {$event['model']} ({$event['registration_number']}) is scheduled for maintenance on {$event['scheduled_date']}.";
                    foreach (fetchAdminEmails($conn) as $email) {
                        sendNotification('email', $email, 'Upcoming Maintenance', $message);
                    }
                    logCronAction("Upcoming maintenance email sent for Vehicle ID: $referenceId.");
                    break;

                case 'booking_reminder':
                    if ($event['email']) {
                        $message = "Reminder: Your booking for {$event['make']} {$event['model']} is scheduled for pickup tomorrow!";
                        sendNotification('email', $event['email'], 'Booking Reminder', $message);
                        logCronAction("Booking reminder sent for Booking ID: $referenceId.");
                    }
                    break;

                default:
                    logCronAction("Unhandled event type: $eventType for Event ID: $eventId.");
                    break;
            }

            // Mark event as processed
            $updateStmt = $conn->prepare("UPDATE timed_events SET status = 'processed', processed_at = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $eventId);

            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update event status for Event ID: $eventId.");
            }

        } catch (Exception $e) {
            logError("Error processing Event ID $eventId: " . $e->getMessage());
            logCronAction("Failed to process Event ID $eventId: " . $e->getMessage());
        }
    }

    // ==========================
    // Expired Payment Cleanup
    // ==========================
    $paymentCleanupQuery = "
        DELETE FROM payment_methods
        WHERE is_default = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    if (!$conn->query($paymentCleanupQuery)) {
        throw new Exception("Failed to clean up expired payment methods: " . $conn->error);
    }
    logCronAction("Expired non-default payment methods cleaned up.");

} catch (Exception $e) {
    logError("Cron Job Error: " . $e->getMessage());
    logCronAction("Error: " . $e->getMessage());
}

logCronAction("Cron job completed.");
