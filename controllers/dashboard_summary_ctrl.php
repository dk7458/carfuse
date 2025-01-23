$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /controllers/dashboard_summary_ctrl.php
require_once BASE_PATH . 'includes/db_connect.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

enforceRole(['admin', 'super_admin'],'/public/login.php'); 

header('Content-Type: application/json');

try {
    // Fetch total bookings
    $bookingsQuery = "SELECT COUNT(*) AS total FROM bookings";
    $bookingsResult = $conn->query($bookingsQuery)->fetch_assoc();

    // Fetch total revenue
    $revenueQuery = "SELECT SUM(total_price) AS total FROM bookings WHERE status = 'paid'";
    $revenueResult = $conn->query($revenueQuery)->fetch_assoc();

    // Fetch fleet availability
    $fleetQuery = "SELECT COUNT(*) AS total, SUM(availability) AS available FROM fleet";
    $fleetResult = $conn->query($fleetQuery)->fetch_assoc();

    // Fetch total users
    $usersQuery = "SELECT COUNT(*) AS total FROM users WHERE role = 'user'";
    $usersResult = $conn->query($usersQuery)->fetch_assoc();

    echo json_encode([
        'success' => true,
        'metrics' => [
            'bookings' => $bookingsResult['total'] ?? 0,
            'revenue' => $revenueResult['total'] ?? 0.00,
            'fleet' => [
                'total' => $fleetResult['total'] ?? 0,
                'available' => $fleetResult['available'] ?? 0
            ],
            'users' => $usersResult['total'] ?? 0,
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
