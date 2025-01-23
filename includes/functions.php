<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
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
function logSensitiveAction($userId, $action, $details) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
}

function sendEmail($to, $subject, $message, $template = null, $data = [], $attachment = null) {
    require_once '/home/u122931475/domains/carfuse.pl/public_html/vendor/autoload.php';

    $mail = new PHPMailer(true);

    try {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.zoho.eu';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreply@carfuse.pl';
        $mail->Password = 'Spierdalaj!23';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Sender and recipient
        $mail->setFrom('noreply@carfuse.pl', 'Carfuse');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;

        if ($template) {
            $message = applyTemplate($template, $data);
        }

        $mail->Body = $message;

        if ($attachment) {
            $mail->addAttachment($attachment);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

function applyTemplate($templateFile, $data) {
    ob_start();
    extract($data);
    include "../templates/emails/$templateFile";
    return ob_get_clean();
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

function sendMQTTNotification($topic, $message) {
    // Ensure the topic and message are properly escaped for shell execution
    $escapedTopic = escapeshellarg($topic);
    $escapedMessage = escapeshellarg($message);
    $mqttCommand = "mosquitto_pub -t $escapedTopic -m $escapedMessage";
    shell_exec($mqttCommand);
}

/**
 * Fetch a notification by its ID.
 * 
 * @param mysqli $conn
 * @param int $notificationId
 * @return array|null
 */
function fetchNotificationById($conn, $notificationId) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Resend a notification.
 * 
 * @param mysqli $conn
 * @param array $notification
 * @return bool
 */
function resendNotification($conn, $notification) {
    // Logic to resend the notification
    // This could involve sending an email or SMS again
    return true;
}

/**
 * Delete a notification.
 * 
 * @param mysqli $conn
 * @param int $notificationId
 * @return bool
 */
function deleteNotification($conn, $notificationId) {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    return $stmt->execute();
}

/**
 * Generate a notification report.
 * 
 * @param mysqli $conn
 * @param string $type
 * @param string $startDate
 * @param string $endDate
 * @return array
 */
function generateNotificationReport($conn, $type, $startDate, $endDate) {
    $query = "SELECT * FROM notifications WHERE type = ? AND sent_at BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $type, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Generate a contract and send it to the user via email.
 * 
 * @param mysqli $conn
 * @param int $userId
 * @param int $vehicleId
 * @param int $bookingId
 * @param string $pickupDate
 * @param string $dropoffDate
 * @param float $totalPrice
 */
function generateAndSendContract($conn, $userId, $vehicleId, $bookingId, $pickupDate, $dropoffDate, $totalPrice) {
    // Logic to generate the contract PDF
    $contractFilePath = generateContractPDF($userId, $vehicleId, $bookingId, $pickupDate, $dropoffDate, $totalPrice);

    // Fetch user email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $userEmail = $user['email'];

    // Send the contract via email
    $subject = "Your Booking Contract";
    $message = "Dear User,\n\nPlease find attached your booking contract.\n\nThank you.";
    sendEmail($userEmail, $subject, $message, null, [], $contractFilePath);
}

/**
 * Fetch all users from the database.
 * 
 * @param mysqli $conn
 * @return array
 */
function fetchUsers($conn) {
    $users = [];
    $query = "SELECT * FROM users";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

/**
 * Count users based on filters.
 * 
 * @param mysqli $conn
 * @param string $search
 * @param string $role
 * @param string $status
 * @return int
 */
function countUsers($conn, $search = '', $role = '', $status = '') {
    $query = "SELECT COUNT(*) AS total FROM users WHERE 1";
    $params = [];
    $types = '';

    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR surname LIKE ? OR email LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    if (!empty($role)) {
        $query .= " AND role = ?";
        $params[] = $role;
        $types .= 's';
    }

    if ($status !== '') {
        $query .= " AND active = ?";
        $params[] = $status;
        $types .= 'i';
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

/**
 * Fetch maintenance logs based on filters.
 * 
 * @param mysqli $conn
 * @param string $search
 * @param string $dateRange
 * @param string $startDate
 * @param string $endDate
 * @param int $offset
 * @param int $limit
 * @return mysqli_result
 */
function fetchMaintenanceLogs($conn, $search = '', $dateRange = '', $startDate = '', $endDate = '', $offset = 0, $limit = 10) {
    $query = "SELECT * FROM maintenance_logs WHERE 1";
    $params = [];
    $types = '';

    if (!empty($search)) {
        $query .= " AND (make LIKE ? OR model LIKE ? OR description LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    if (!empty($dateRange)) {
        if ($dateRange === 'last_week') {
            $query .= " AND maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        } elseif ($dateRange === 'last_month') {
            $query .= " AND maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        }
    }

    if (!empty($startDate)) {
        $query .= " AND maintenance_date >= ?";
        $params[] = $startDate;
        $types .= 's';
    }

    if (!empty($endDate)) {
        $query .= " AND maintenance_date <= ?";
        $params[] = $endDate;
        $types .= 's';
    }

    $query .= " LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    $types .= 'ii';

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Count maintenance logs based on filters.
 * 
 * @param mysqli $conn
 * @param string $search
 * @param string $dateRange
 * @param string $startDate
 * @param string $endDate
 * @return int
 */
function countMaintenanceLogs($conn, $search = '', $dateRange = '', $startDate = '', $endDate = '') {
    $query = "SELECT COUNT(*) AS total FROM maintenance_logs WHERE 1";
    $params = [];
    $types = '';

    if (!empty($search)) {
        $query .= " AND (make LIKE ? OR model LIKE ? OR description LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    if (!empty($dateRange)) {
        if ($dateRange === 'last_week') {
            $query .= " AND maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        } elseif ($dateRange === 'last_month') {
            $query .= " AND maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        }
    }

    if (!empty($startDate)) {
        $query .= " AND maintenance_date >= ?";
        $params[] = $startDate;
        $types .= 's';
    }

    if (!empty($endDate)) {
        $query .= " AND maintenance_date <= ?";
        $params[] = $endDate;
        $types .= 's';
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}
?>
