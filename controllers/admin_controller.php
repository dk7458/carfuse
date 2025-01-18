<?php
require __DIR__ . '../includes/db_connect.php';
require __DIR__ . '../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

// Handle signature deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_signature') {
    $signatureId = intval($_POST['signature_id']);
    $stmt = $conn->prepare("DELETE FROM digital_signatures WHERE id = ?");
    $stmt->bind_param("i", $signatureId);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Signature deleted successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to delete signature.']);
    }
    exit;
}

// Additional actions can be added here
?>
