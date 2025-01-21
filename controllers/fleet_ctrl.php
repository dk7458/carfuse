<?php
// File Path: /controllers/fleet_ctrl.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session_middleware.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak dostępu.']);
    exit;
}

header('Content-Type: application/json');

try {
    // Handle adding a new vehicle
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add_vehicle') {
        $make = htmlspecialchars($_POST['make']);
        $model = htmlspecialchars($_POST['model']);
        $registrationNumber = htmlspecialchars($_POST['registration_number']);
        $availability = intval($_POST['availability']);

        $stmt = $conn->prepare("INSERT INTO fleet (make, model, registration_number, availability, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssi", $make, $model, $registrationNumber, $availability);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Pojazd został dodany pomyślnie.']);
        } else {
            throw new Exception("Błąd podczas dodawania pojazdu.");
        }
        exit;
    }

    // Handle updating a vehicle's availability
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'toggle_availability') {
        $vehicleId = intval($_POST['vehicle_id']);
        $availability = intval($_POST['availability']);

        $stmt = $conn->prepare("UPDATE fleet SET availability = ? WHERE id = ?");
        $stmt->bind_param("ii", $availability, $vehicleId);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Dostępność pojazdu została zaktualizowana.']);
        } else {
            throw new Exception("Błąd podczas zmiany dostępności.");
        }
        exit;
    }

    // Handle deleting a vehicle
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete_vehicle') {
        $vehicleId = intval($_POST['vehicle_id']);

        $stmt = $conn->prepare("DELETE FROM fleet WHERE id = ?");
        $stmt->bind_param("i", $vehicleId);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Pojazd został usunięty.']);
        } else {
            throw new Exception("Błąd podczas usuwania pojazdu.");
        }
        exit;
    }

    // Fetch fleet data for visualization
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'visualization_data') {
        $data = [
            'availability' => [],
            'maintenance' => []
        ];

        // Fetch availability data
        $availabilityQuery = "SELECT availability, COUNT(*) AS count FROM fleet GROUP BY availability";
        $availabilityResult = $conn->query($availabilityQuery);
        while ($row = $availabilityResult->fetch_assoc()) {
            $data['availability'][] = [
                'status' => $row['availability'] ? 'Dostępny' : 'Niedostępny',
                'count' => $row['count']
            ];
        }

        // Fetch maintenance data
        $maintenanceQuery = "SELECT 
                                CASE 
                                    WHEN last_maintenance_date <= DATE_SUB(NOW(), INTERVAL 6 MONTH) THEN 'Przegląd Wymagany'
                                    ELSE 'Przegląd Aktualny'
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
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
