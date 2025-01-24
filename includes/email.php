<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once BASE_PATH . '/vendor/autoload.php';
/**
 * Sends an email using PHPMailer.
 *
 * @param string $to Recipient email address.
 * @param string $subject Email subject.
 * @param string $message Email body content.
 * @param string|null $template Optional template file name.
 * @param array $data Data to be passed to the template.
 * @return bool True if email sent successfully, false otherwise.
 */
function sendEmail($to, $subject, $message, $template = null, $data = []) {
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

?>
