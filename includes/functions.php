<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /includes/functions.php
 * Purpose: Contains globally applicable utility functions for the Carfuse application.
 *
 * Changelog:
 * - Retained globally applicable functions only.
 * - Removed domain-specific functions to modular files (e.g., email_functions.php).
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '/home/u122931475/domains/carfuse.pl/public_html/vendor/autoload.php'; // Ensure PHPMailer is autoloaded

/**
 * Enforces role-based access control (RBAC) for the current user.
 *
 * @param string|array $requiredRoles A single role or an array of allowed roles.
 * @param string|null $redirectPage The page to redirect to if access is denied (optional).
 */
function enforceRole($requiredRoles, $redirectPage = '/public/403.php') {
    if (!isset($_SESSION['user_role'])) {
        // Redirect to login if no role is set
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: /public/login.php');
        exit;
    }

    $userRole = $_SESSION['user_role'];

    // Check if the user role matches the required role(s)
    if (is_array($requiredRoles)) {
        if (!in_array($userRole, $requiredRoles)) {
            header("Location: $redirectPage");
            exit;
        }
    } elseif ($userRole !== $requiredRoles) {
        header("Location: $redirectPage");
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isSuperAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
}

function isUser() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
}

function hasAccess($requiredRole) {
    $rolesHierarchy = ['user' => 1, 'admin' => 2, 'super_admin' => 3];
    return isset($_SESSION['user_role']) && $rolesHierarchy[$_SESSION['user_role']] >= $rolesHierarchy[$requiredRole];
}

/**
 * Generate a CSRF token for forms.
 * 
 * @return string
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token.
 * 
 * @param string $token
 * @return bool
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log actions to the database.
 * 
 * @param int $userId
 * @param string $action
 * @param string|null $details
 */
function logAction($userId, $action, $details = null) {
    global $conn;
    $sql = "INSERT INTO logs (user_id, action, details, timestamp) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
}

/**
 * Sanitize and validate email input.
 * 
 * @param string $email
 * @return string|bool
 */
function sanitizeEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Redirect to a given URL.
 * 
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit();
}
?>
