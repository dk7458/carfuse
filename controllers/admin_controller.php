<?php

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

enforceRole(['admin', 'super_admin'],'/public/login.php'); 

header('Content-Type: application/json');

// Handle signature deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_signature') {
    try {
        $signatureId = intval($_POST['signature_id']);
        if (!$signatureId) {
            throw new Exception("Invalid signature ID.");
        }

        $stmt = $conn->prepare("DELETE FROM digital_signatures WHERE id = ?");
        $stmt->bind_param("i", $signatureId);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Signature deleted successfully.']);
        } else {
            throw new Exception("Failed to delete signature.");
        }
    } catch (Exception $e) {
        logError($e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Additional actions can be added here
?>
