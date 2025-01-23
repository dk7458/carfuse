<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

enforceRole(['admin', 'super_admin'],'/public/login.php'); 

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'add_vehicle':
                $make = htmlspecialchars($_POST['make']);
                $model = htmlspecialchars($_POST['model']);
                $registrationNumber = htmlspecialchars($_POST['registration_number']);
                $availability = intval($_POST['availability']);

                $stmt = $conn->prepare("INSERT INTO fleet (make, model, registration_number, availability, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssi", $make, $model, $registrationNumber, $availability);

                if ($stmt->execute()) {
                    echo json_encode(['success' => 'Vehicle added successfully.']);
                } else {
                    throw new Exception("Error adding vehicle.");
                }
                break;

            case 'toggle_availability':
                $vehicleId = intval($_POST['vehicle_id']);
                $availability = intval($_POST['availability']);

                $stmt = $conn->prepare("UPDATE fleet SET availability = ? WHERE id = ?");
                $stmt->bind_param("ii", $availability, $vehicleId);

                if ($stmt->execute()) {
                    echo json_encode(['success' => 'Vehicle availability updated successfully.']);
                } else {
                    throw new Exception("Error updating availability.");
                }
                break;

            case 'delete_vehicle':
                $vehicleId = intval($_POST['vehicle_id']);
                if (!$vehicleId) {
                    throw new Exception("Invalid vehicle ID.");
                }

                $stmt = $conn->prepare("DELETE FROM fleet WHERE id = ?");
                $stmt->bind_param("i", $vehicleId);

                if ($stmt->execute()) {
                    echo json_encode(['success' => 'Vehicle deleted successfully.']);
                } else {
                    throw new Exception("Error deleting vehicle.");
                }
                break;

            default:
                throw new Exception("Unknown action: $action");
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'visualization_data') {
        $data = [
            'availability' => [],
            'maintenance' => []
        ];

        // Fetch availability data
        $availabilityQuery = "SELECT availability, COUNT(*) AS count FROM fleet GROUP BY availability";
        $availabilityResult = $conn->query($availabilityQuery);
        while ($row = $availabilityResult->fetch_assoc()) {
            $data['availability'][] = [
                'status' => $row['availability'] ? 'Available' : 'Unavailable',
                'count' => $row['count']
            ];
        }

        // Fetch maintenance data
        $maintenanceQuery = "SELECT 
                                CASE 
                                    WHEN last_maintenance_date <= DATE_SUB(NOW(), INTERVAL 6 MONTH) THEN 'Maintenance Required'
                                    ELSE 'Maintenance Current'
                                END AS maintenance_status,
                                COUNT(*) AS count 
                              FROM fleet 
                              GROUP BY maintenance_status";
        $maintenanceResult = $conn->query($maintenanceQuery);
        while ($row = $maintenanceResult->fetch_assoc()) {
            $data['maintenance'][] = [
                'status' => $row['maintenance_status'],
                'count' => $row['count']
            ];
        }

        echo json_encode($data);
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    logError($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
