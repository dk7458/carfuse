<?php
require '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';


// Ensure the user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

// Handle contract generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_contract') {
    $bookingId = intval($_POST['booking_id']);

    // Fetch booking details
    $stmt = $conn->prepare("
        SELECT b.id, u.name AS user_name, f.make, f.model, b.pickup_date, b.dropoff_date
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN fleet f ON b.vehicle_id = f.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->bind_param("ii", $bookingId, $_SESSION['user_id']);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found.']);
        exit;
    }

    // Generate PDF (simplified for demonstration)
    $fileName = "contract_{$bookingId}.pdf";
    $filePath = "../documents/contracts/$fileName";

    $pdfContent = "
        Umowa najmu pojazdu\n
        Użytkownik: {$booking['user_name']}\n
        Pojazd: {$booking['make']} {$booking['model']}\n
        Data odbioru: {$booking['pickup_date']}\n
        Data zwrotu: {$booking['dropoff_date']}
    ";
    file_put_contents($filePath, $pdfContent);

    // Save to database
    $stmt = $conn->prepare("UPDATE bookings SET rental_contract_pdf = ? WHERE id = ?");
    $stmt->bind_param("si", $fileName, $bookingId);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Contract generated successfully.', 'file_path' => $filePath]);
    } else {
        echo json_encode(['error' => 'Failed to save contract.']);
    }
    exit;
}

// Additional contract-related actions can be added here
?>
