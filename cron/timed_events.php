<?php
require_once BASE_PATH . '/functions/email.php';declare(strict_types=1);

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/db_connect.php';
require_once BASE_PATH . 'functions/global.php';
require_once BASE_PATH . 'includes/email.php';

header('Content-Type: text/plain; charset=UTF-8');

// Constants
const EVENT_TYPE_MAINTENANCE_OVERDUE = 'maintenance_overdue';
const EVENT_TYPE_MAINTENANCE_UPCOMING = 'maintenance_upcoming';
const EVENT_TYPE_BOOKING_REMINDER = 'booking_reminder';

// Log directory and file
$logDirectory = __DIR__ . '/../logs/';
$logFile = $logDirectory . 'cron_timed_events.log';

// Ensure log directory exists
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0755, true);
}

/**
 * Logs a message to the log file and outputs it.
 *
 * @param string $message
 * @param string $severity
 */
function logCronAction(string $message, string $severity = 'INFO'): void {
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s]');
    $formattedMessage = "$timestamp [$severity] $message\n";
    echo $formattedMessage;
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

/**
 * Processes a single event.
 *
 * @param mysqli $conn
 * @param array $event
 * @throws Exception
 */
function processEvent(mysqli $conn, array $event): void {
    $eventId = (int) $event['id'];
    $eventType = $event['event_type'];
    $referenceId = (int) $event['reference_id'];

    switch ($eventType) {
        case EVENT_TYPE_MAINTENANCE_OVERDUE:
            $message = "Vehicle {$event['make']} {$event['model']} ({$event['registration_number']}) is overdue for maintenance!";
            foreach (fetchAdminEmails($conn) as $email) {
                sendEmail($email, 'Overdue Maintenance', $message);
            }
            logCronAction("Overdue maintenance email sent for Vehicle ID: $referenceId.", 'INFO');
            break;

        case EVENT_TYPE_MAINTENANCE_UPCOMING:
            $message = "Vehicle {$event['make']} {$event['model']} ({$event['registration_number']}) is scheduled for maintenance on {$event['scheduled_date']}.";
            foreach (fetchAdminEmails($conn) as $email) {
                sendEmail($email, 'Upcoming Maintenance', $message);
            }
            logCronAction("Upcoming maintenance email sent for Vehicle ID: $referenceId.", 'INFO');
            break;

        case EVENT_TYPE_BOOKING_REMINDER:
            if ($event['email']) {
                $message = "Reminder: Your booking for {$event['make']} {$event['model']} is scheduled for pickup tomorrow!";
                sendEmail($event['email'], 'Booking Reminder', $message);
                logCronAction("Booking reminder sent for Booking ID: $referenceId.", 'INFO');
            }
            break;

        default:
            logCronAction("Unhandled event type: $eventType for Event ID: $eventId.", 'WARNING');
            break;
    }

    // Mark event as processed
    $updateStmt = $conn->prepare("UPDATE timed_events SET status = 'processed', processed_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $eventId);

    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update event status for Event ID: $eventId.");
    }
}

/**
 * Cleans up expired payment methods.
 *
 * @param mysqli $conn
 * @throws Exception
 */
function cleanupExpiredPayments(mysqli $conn): void {
    $paymentCleanupQuery = "
        DELETE FROM payment_methods
        WHERE is_default = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    if (!$conn->query($paymentCleanupQuery)) {
        throw new Exception("Failed to clean up expired payment methods: " . $conn->error);
    }
    logCronAction("Expired non-default payment methods cleaned up.", 'INFO');
}

try {
    logCronAction("Cron job started.", 'INFO');

    // Fetch and process pending events
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
        LEFT JOIN fleet f ON te.reference_id = f.id AND te.event_type IN (?, ?)
        LEFT JOIN users u ON te.reference_id = u.id AND te.event_type = ?
        WHERE te.status = 'pending' AND te.scheduled_date <= CURDATE()
    ";
    $stmt = $conn->prepare($query);
    $eventTypeOverdue = EVENT_TYPE_MAINTENANCE_OVERDUE;
    $eventTypeUpcoming = EVENT_TYPE_MAINTENANCE_UPCOMING;
    $eventTypeReminder = EVENT_TYPE_BOOKING_REMINDER;

    $stmt->bind_param('sss', $eventTypeOverdue, $eventTypeUpcoming, $eventTypeReminder);
    $stmt->execute();
    $events = $stmt->get_result();

    if ($events->num_rows === 0) {
        logCronAction("No pending events found.", 'INFO');
    } else {
        while ($event = $events->fetch_assoc()) {
            try {
                processEvent($conn, $event);
            } catch (Exception $e) {
                logCronAction("Error processing Event ID {$event['id']}: " . $e->getMessage(), 'ERROR');
            }
        }
    }

    // Cleanup expired payment methods
    cleanupExpiredPayments($conn);

} catch (Exception $e) {
    logCronAction("Cron Job Error: " . $e->getMessage(), 'ERROR');
}

logCronAction("Cron job completed.", 'INFO');
?>
