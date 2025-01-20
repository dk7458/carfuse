<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_middleware.php';
require_once __DIR__ . '/../includes/user_queries.php';

header('Content-Type: application/json');

// Ensure the user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];

    // Add a new payment method
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_payment_method') {
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token.");
        }

        $methodName = sanitizeInput($_POST['method_name']);
        $details = sanitizeInput($_POST['details']);

        $stmt = $conn->prepare("INSERT INTO payment_methods (user_id, method_name, details) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $methodName, $details);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Payment method added successfully.']);
        } else {
            throw new Exception("Failed to add payment method.");
        }
        exit;
    }

    // Delete a payment method
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_payment_method') {
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token.");
        }

        $methodId = intval($_POST['method_id']);

        $stmt = $conn->prepare("DELETE FROM payment_methods WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $methodId, $userId);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Payment method deleted successfully.']);
        } else {
            throw new Exception("Failed to delete payment method.");
        }
        exit;
    }

    // Set a default payment method
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_default_payment_method') {
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token.");
        }

        $methodId = intval($_POST['method_id']);

        $conn->begin_transaction();
        try {
            // Clear existing default methods
            $conn->query("UPDATE payment_methods SET is_default = 0 WHERE user_id = $userId");

            // Set the new default method
            $stmt = $conn->prepare("UPDATE payment_methods SET is_default = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $methodId, $userId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to set default payment method.");
            }

            $conn->commit();
            echo json_encode(['success' => 'Default payment method updated successfully.']);
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        exit;
    }

} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>

