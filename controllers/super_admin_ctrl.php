<?php

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


header('Content-Type: application/json');

// Ensure user is a super_admin
enforceRole('super_admin');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_user_role':
                $userId = intval($_POST['user_id']);
                $role = $_POST['role'];

                if (!in_array($role, ['user', 'admin', 'super_admin'])) {
                    throw new Exception("Nieprawidłowa rola.");
                }

                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $role, $userId);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception("Nie udało się zaktualizować roli użytkownika.");
                }
                break;

            case 'delete_user':
                $userId = intval($_POST['user_id']);
                if ($userId === $_SESSION['user_id']) {
                    throw new Exception("Nie można usunąć samego siebie.");
                }

                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception("Nie udało się usunąć użytkownika.");
                }
                break;

            default:
                throw new Exception("Nieznana akcja.");
        }
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
