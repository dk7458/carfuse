<?php

namespace App\Services;

use PDO;
use Psr\Log\LoggerInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationService
{
    private PDO $db;
    private LoggerInterface $logger;
    private array $config;
    private array $retryCount = [];
    private const MAX_RETRY_ATTEMPTS = 3;

    public function __construct(PDO $db, LoggerInterface $logger, array $config)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Send notification to a user
     */
    public function sendNotification(
        int $userId,
        string $type,
        string $message,
        array $options = []
    ): bool {
        try {
            // Store notification in the database
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, message, sent_at, is_read)
                VALUES (:user_id, :type, :message, NOW(), 0)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'type' => $type,
                'message' => $message,
            ]);

            $this->logger->info('Notification stored in database', [
                'user_id' => $userId,
                'type' => $type,
                'message' => $message,
            ]);

            // Handle specific notification delivery methods
            $result = match ($type) {
                'email' => $this->sendEmail($options['email'] ?? '', $message, $options['subject'] ?? 'Notification', $options['booking_details'] ?? []),
                'sms' => $this->sendSMS($options['phone'] ?? '', $message),
                'webhook' => $this->sendWebhook($options['url'] ?? '', $message),
                'push' => $this->sendPushNotification($options['device_token'] ?? '', $message, $options['is_admin'] ?? false),
                default => throw new \InvalidArgumentException("Unsupported notification type: $type"),
            };

            if ($result) {
                $this->logger->info('Notification sent successfully', [
                    'user_id' => $userId,
                    'type' => $type,
                    'message' => $message,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Notification sending failed', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send an email using PHPMailer
     */
    private function sendEmail(string $to, string $message, string $subject, array $bookingDetails): bool
    {
        if (empty($to)) {
            $this->logger->warning('Email not sent: No recipient specified');
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_user'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_secure'] ?? 'tls';
            $mail->Port = $this->config['smtp_port'];

            // Email settings
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);

            // Format email with booking details
            $formattedMessage = $this->formatEmailMessage($message, $bookingDetails);
            $mail->Body = $formattedMessage;

            $mail->send();

            $this->logger->info("Email sent to $to");
            return true;
        } catch (Exception $e) {
            $this->logger->error('Email sending failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Format email message with booking details
     */
    private function formatEmailMessage(string $message, array $bookingDetails): string
    {
        $details = '';
        foreach ($bookingDetails as $key => $value) {
            $details .= "<p><strong>$key:</strong> $value</p>";
        }
        return "<p>$message</p>$details";
    }

    /**
     * Send an SMS
     */
    private function sendSMS(string $phone, string $message): bool
    {
        if (empty($phone)) {
            $this->logger->warning('SMS not sent: No phone number specified');
            return false;
        }

        try {
            // Simulate SMS API integration
            $this->logger->info("Sending SMS to $phone: $message");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('SMS sending failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send a webhook notification
     */
    private function sendWebhook(string $url, string $message): bool
    {
        if (empty($url)) {
            $this->logger->warning('Webhook not sent: No URL specified');
            return false;
        }

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $message]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response === false) {
                throw new \Exception('Webhook request failed');
            }

            $this->logger->info("Webhook sent to $url");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Webhook sending failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send a push notification
     */
    private function sendPushNotification(string $deviceToken, string $message, bool $isAdmin): bool
    {
        if (empty($deviceToken)) {
            $this->logger->warning('Push notification not sent: No device token specified');
            return false;
        }

        try {
            $payload = [
                'to' => $deviceToken,
                'notification' => [
                    'title' => $isAdmin ? 'Admin Notification' : 'User Notification',
                    'body' => $message,
                ],
            ];

            $this->sendFCMRequest($payload);
            $this->logger->info("Push notification sent to device token $deviceToken");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Push notification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send Firebase Cloud Messaging (FCM) request
     */
    private function sendFCMRequest(array $payload): void
    {
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: key=' . $this->config['fcm_api_key'],
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);

        if (curl_errno($ch) || !$response || json_decode($response, true)['failure'] > 0) {
            throw new \Exception('FCM push notification failed: ' . curl_error($ch));
        }

        curl_close($ch);
    }
}
