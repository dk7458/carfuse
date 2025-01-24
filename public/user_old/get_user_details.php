<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once BASE_PATH . 'functions/global.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


// Ensure the user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

$userId = $_SESSION['user_id'];
$userDetails = $conn->query("SELECT name, surname, email, address, pesel_or_id, phone FROM users WHERE id = $userId")->fetch_assoc();

echo json_encode($userDetails);
?>
