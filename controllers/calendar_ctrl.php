<?php
/**
 * File Path: /controllers/calendar_ctrl.php
 * Description: Handles calendar data for users, admins, and super admins, including bookings, availability, and rescheduling.
 * Changelog:
 * - Added admin rescheduling functionality.
 * - Included conflict-checking for rescheduled bookings.
 * - Improved error handling and modularized reusable database queries.
 * - Enhanced user calendar with more intuitive rescheduling interactions and conflict alerts.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/functions.php';


// Enforce role-based access
enforceRole(['user', 'admin', 'super_admin'], '/public/login.php');

header('Content-Type: application/json');

try {
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    $action = $_REQUEST['action'] ?? ''; // Handles both GET and POST actions

    // Fetch calendar data (GET)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'fetch_calendar_data') {
            $stmt = null;

            // User calendar: Fetch personal bookings
            if ($role === 'user') {
                $stmt = $conn->prepare("
                    SELECT 
                        b.id, 
                        f.make, 
                        f.model, 
                        b.pickup_date AS start, 
                        b.dropoff_date AS end, 
                        CONCAT(f.make, ' ', f.model) AS title 
                    FROM bookings b 
                    JOIN fleet f ON b.vehicle_id = f.id 
                    WHERE b.user_id = ? 
                    ORDER BY b.pickup_date
                ");
                $stmt->bind_param('i', $userId);
            }

            // Admin calendar: Fetch all bookings and availability
            elseif (in_array($role, ['admin', 'super_admin'])) {
                $stmt = $conn->prepare("
                    SELECT 
                        b.id, 
                        f.make, 
                        f.model, 
                        b.pickup_date AS start, 
                        b.dropoff_date AS end, 
                        CONCAT(f.make, ' ', f.model) AS title, 
                        'booking' AS type 
                    FROM bookings b 
                    JOIN fleet f ON b.vehicle_id = f.id
                    UNION
                    SELECT 
                        a.vehicle_id AS id, 
                        f.make, 
                        f.model, 
                        a.date AS start, 
                        a.date AS end, 
                        CONCAT(f.make, ' ', f.model, ' - Available') AS title, 
                        'availability' AS type 
                    FROM availability a 
                    JOIN fleet f ON a.vehicle_id = f.id
                    WHERE a.status = 'available'
                    ORDER BY start
                ");
            }

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                $events = [];

                while ($row = $result->fetch_assoc()) {
                    $events[] = [
                        'id' => $row['id'],
                        'title' => $row['title'],
                        'start' => $row['start'],
                        'end' => $row['end'],
                        'type' => $row['type'] ?? 'booking',
                    ];
                }
                $stmt->close();

                echo json_encode(['success' => true, 'events' => $events]);
                exit;
            }
        }

        throw new Exception("Invalid action.");
    }

    // Calendar updates (POST, Admin Only)
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!in_array($role, ['admin', 'super_admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied.']);
            exit;
        }

        switch ($action) {
            case 'add_availability':
                $vehicleId = intval($_POST['vehicle_id']);
                $date = $_POST['date'];

                if (empty($vehicleId) || empty($date)) {
                    throw new Exception("Vehicle ID and date are required.");
                }

                $stmt = $conn->prepare("
                    INSERT INTO availability (vehicle_id, date, status) 
                    VALUES (?, ?, 'available') 
                    ON DUPLICATE KEY UPDATE status = 'available'
                ");
                $stmt->bind_param('is', $vehicleId, $date);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Availability added.']);
                } else {
                    throw new Exception("Failed to add availability.");
                }
                $stmt->close();
                break;

            case 'remove_availability':
                $vehicleId = intval($_POST['vehicle_id']);
                $date = $_POST['date'];

                if (empty($vehicleId) || empty($date)) {
                    throw new Exception("Vehicle ID and date are required.");
                }

                $stmt = $conn->prepare("
                    DELETE FROM availability 
                    WHERE vehicle_id = ? AND date = ?
                ");
                $stmt->bind_param('is', $vehicleId, $date);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Availability removed.']);
                } else {
                    throw new Exception("Failed to remove availability.");
                }
                $stmt->close();
                break;

            case 'reschedule_booking':
                $bookingId = intval($_POST['booking_id']);
                $newStart = $_POST['new_start'];
                $newEnd = $_POST['new_end'];

                if (empty($bookingId) || empty($newStart) || empty($newEnd)) {
                    throw new Exception("Booking ID and new dates are required.");
                }

                // Check for conflicting bookings
                $stmt = $conn->prepare("
                    SELECT COUNT(*) 
                    FROM bookings 
                    WHERE id != ? AND vehicle_id = (
                        SELECT vehicle_id FROM bookings WHERE id = ?
                    ) AND NOT (
                        dropoff_date <= ? OR pickup_date >= ?
                    )
                ");
                $stmt->bind_param('iiss', $bookingId, $bookingId, $newStart, $newEnd);
                $stmt->execute();
                $conflicts = $stmt->get_result()->fetch_row()[0];
                $stmt->close();

                if ($conflicts > 0) {
                    throw new Exception("New dates conflict with existing bookings.");
                }

                // Update booking dates
                $stmt = $conn->prepare("
                    UPDATE bookings 
                    SET pickup_date = ?, dropoff_date = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param('ssi', $newStart, $newEnd, $bookingId);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Booking rescheduled successfully.']);
                } else {
                    throw new Exception("Failed to reschedule booking.");
                }
                $stmt->close();
                break;

            default:
                throw new Exception("Invalid action.");
        }
    }

    // Invalid request method
    else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    logError($e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
