<?php
require_once BASE_PATH . '/functions/email.php';require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once BASE_PATH . '/vendor/autoload.php';

/**
 * File Path: /functions/email.php
 * Purpose: Handles all email-related operations, including sending emails, applying templates, and generating contracts.
 *
 * Changelog:
 * - Refactored from functions.php to email.php (Date).
 * - Improved error handling for PHPMailer.
 * - Modularized email template application and contract generation.
 */

/**
 * Sends an email using PHPMailer.
 *
 * @param string $to Recipient email address.
 * @param string $subject Email subject.
 * @param string $message Email body content.
 * @param string|null $template Optional template file name.
 * @param array $data Data to be passed to the template.
 * @param string|null $attachment Optional file path for attachment.
 * @return bool True if email sent successfully, false otherwise.
 */
function sendEmail($to, $subject, $message, $template = null, $data = [], $attachment = null) {
    $mail = new PHPMailer(true);

    try {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USER');
        $mail->Password = getenv('SMTP_PASS');
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Sender and recipient
        $mail->setFrom(getenv('SMTP_FROM'), 'Carfuse');
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

/**
 * Applies a template to an email body.
 *
 * @param string $templateFile Path to the template file.
 * @param array $data Associative array of template data.
 * @return string Rendered template content.
 */
function applyTemplate($templateFile, $data) {
    ob_start();
    extract($data);

    // Include the theme CSS from /public/assets
    $themeCssPath = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/theme.css';
    $themeCss = file_exists($themeCssPath) ? file_get_contents($themeCssPath) : '';

    echo "<style>{$themeCss}</style>";
    include "/path/to/templates/emails/$templateFile";

    return ob_get_clean();
}

/**
 * Generates the email header.
 *
 * @return string The email header HTML.
 */
function generateEmailHeader() {
    return '
        <div style="background-color: #007bff; color: white; padding: 10px; text-align: center; border-radius: 8px 8px 0 0;">
            <h1>Carfuse</h1>
        </div>
    ';
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
 * Generate a contract PDF.
 * 
 * @param int $userId
 * @param int $vehicleId
 * @param int $bookingId
 * @param string $pickupDate
 * @param string $dropoffDate
 * @param float $totalPrice
 * @return string Path to the generated PDF file
 */
function generateContractPDF($userId, $vehicleId, $bookingId, $pickupDate, $dropoffDate, $totalPrice) {
    // Logic to generate the contract PDF
    // This is a placeholder function. You need to implement the actual PDF generation logic.
    $pdfFilePath = "/path/to/generated/contract_$bookingId.pdf";
    // Generate the PDF and save it to $pdfFilePath
    return $pdfFilePath;
}
?>
