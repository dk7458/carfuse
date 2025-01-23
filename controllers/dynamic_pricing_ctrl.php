<?php
/**
 * File Path: /controllers/dynamic_pricing_ctrl.php
 * Description: Manages dynamic pricing rules based on demand, season, and availability.
 * Changelog:
 * - Added support for creating, editing, and logging dynamic pricing rules.
 * - Improved error handling and validation.
 * - Added functionality to delete pricing rules.
 * - Integrated logs for each rule change to facilitate auditing.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


header('Content-Type: application/json');

// Enforce admin or super admin access
enforceRole(['admin', 'super_admin'], '/public/login.php');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create_rule':
                $ruleName = $_POST['rule_name'] ?? '';
                $criteria = $_POST['criteria'] ?? '';
                $adjustment = floatval($_POST['adjustment'] ?? 0);

                if (empty($ruleName) || empty($criteria) || $adjustment === 0) {
                    throw new Exception("Invalid data for creating pricing rule.");
                }

                $stmt = $conn->prepare("
                    INSERT INTO pricing_rules (rule_name, criteria, adjustment) 
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param('ssd', $ruleName, $criteria, $adjustment);
                $stmt->execute();
                $stmt->close();

                logAction($_SESSION['user_id'], 'create_rule', "Created pricing rule: $ruleName");
                echo json_encode(['success' => 'Pricing rule created successfully.']);
                break;

            case 'edit_rule':
                $ruleId = intval($_POST['rule_id']);
                $ruleName = $_POST['rule_name'] ?? '';
                $criteria = $_POST['criteria'] ?? '';
                $adjustment = floatval($_POST['adjustment'] ?? 0);

                if ($ruleId === 0 || empty($ruleName) || empty($criteria) || $adjustment === 0) {
                    throw new Exception("Invalid data for editing pricing rule.");
                }

                $stmt = $conn->prepare("
                    UPDATE pricing_rules 
                    SET rule_name = ?, criteria = ?, adjustment = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param('ssdi', $ruleName, $criteria, $adjustment, $ruleId);
                $stmt->execute();
                $stmt->close();

                logAction($_SESSION['user_id'], 'edit_rule', "Edited pricing rule: $ruleName");
                echo json_encode(['success' => 'Pricing rule edited successfully.']);
                break;

            case 'delete_rule':
                $ruleId = intval($_POST['rule_id']);

                if ($ruleId === 0) {
                    throw new Exception("Invalid rule ID.");
                }

                $stmt = $conn->prepare("DELETE FROM pricing_rules WHERE id = ?");
                $stmt->bind_param("i", $ruleId);
                $stmt->execute();
                $stmt->close();

                logAction($_SESSION['user_id'], 'delete_rule', "Deleted pricing rule ID: $ruleId");
                echo json_encode(['success' => 'Pricing rule deleted successfully.']);
                break;

            default:
                throw new Exception("Unknown action.");
        }
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
