<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /functions/vehicle.php
 * Purpose: Handles all vehicle-related operations, including fetching vehicle details.
 *
 * Changelog:
 * - Refactored from functions.php to vehicle.php (Date).
 * - Improved error handling for invalid vehicle IDs.
 */

/**
 * Fetches details of a specific vehicle from the database.
 *
 * @global mysqli $db Database connection.
 * @param int $vehicleId Vehicle ID.
 * @return array Associative array of vehicle details.
 * @throws Exception If validation fails or a database error occurs.
 */
function getVehicleDetails($vehicleId) {
    global $db;

    // Validate vehicle ID
    if (empty($vehicleId) || !is_int($vehicleId) || $vehicleId <= 0) {
        throw new Exception("Invalid vehicle ID.");
    }

    // Prepare SQL query to fetch vehicle details
    $query = "SELECT id, make, model, year, price, status FROM vehicles WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $vehicleId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if vehicle exists
    if ($result->num_rows === 0) {
        throw new Exception("Vehicle not found.");
    }

    // Fetch vehicle data
    $vehicle = $result->fetch_assoc();

    return $vehicle;
}
?>
