<?php
/**
 * File Path: /includes/calendar_helpers.php
 * Description: Provides helper functions for calendar conflict detection and scheduling.
 * Changelog:
 * - Added `detectConflicts` function to identify overlapping bookings.
 * - Added `scheduleEvent` function to handle event scheduling.
 */

/**
 * Detects conflicts for overlapping bookings.
 *
 * @param mysqli $conn Database connection.
 * @param int $vehicleId Vehicle ID.
 * @param string $startDate Start date of the booking.
 * @param string $endDate End date of the booking.
 * @param int|null $excludeBookingId Booking ID to exclude from conflict check (optional).
 * @return bool True if a conflict is detected, false otherwise.
 */
function detectConflicts($conn, $vehicleId, $startDate, $endDate, $excludeBookingId = null) {
    $query = "
        SELECT COUNT(*) AS conflict_count 
        FROM bookings 
        WHERE vehicle_id = ? 
        AND NOT (dropoff_date <= ? OR pickup_date >= ?)
    ";
    $params = [$vehicleId, $startDate, $endDate];
    $types = "iss";

    if ($excludeBookingId) {
        $query .= " AND id != ?";
        $params[] = $excludeBookingId;
        $types .= "i";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result['conflict_count'] > 0;
}

/**
 * Schedules an event in the calendar.
 *
 * @param mysqli $conn Database connection.
 * @param array $eventData Event data (vehicle_id, user_id, start_date, end_date, etc.).
 * @return bool True if the event is scheduled successfully, false otherwise.
 */
function scheduleEvent($conn, $eventData) {
    $stmt = $conn->prepare("
        INSERT INTO bookings (vehicle_id, user_id, pickup_date, dropoff_date, total_price, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iissi", $eventData['vehicle_id'], $eventData['user_id'], $eventData['start_date'], $eventData['end_date'], $eventData['total_price']);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
?>
