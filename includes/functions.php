<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

function sendEmail($to, $subject, $message, $template = null, $data = []) {
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
?>

<?php

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
 * Send a notification via email or SMS (future extension).
 * 
 * @param string $type 'email' or 'sms'
 * @param string $recipient
 * @param string $subject
 * @param string $message
 */
function sendNotification($type, $recipient, $subject, $message) {
    if ($type === 'email') {
        sendEmail($recipient, $subject, $message);
    }
    // Extend for SMS support
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



?>
