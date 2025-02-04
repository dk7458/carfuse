<?php

namespace App\Services;

use PDO;
use Psr\Log\LoggerInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * NotificationService
 *
 * Handles various notification types (email, SMS, webhook, push notifications).
 */
class NotificationService
{
    private PDO $pdo;
    private LoggerInterface $logger;
    private array $config;

    public function __construct(PDO $pdo, LoggerInterface $logger, array $config)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Send a notification
     */
    public function sendNotification(int $userId, string $type, string $message, array $options = []): bool
    {
        try {
            $this->storeNotification($userId, $type, $message);
            return $this->dispatchNotification($userId, $type, $message, $options);
        } catch (\Exception $e) {
            $this->logger->error('Notification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Store notification in the database
     */
    private function storeNotification(int $userId, string $type, string $message): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, type, message, sent_at, is_read)
            VALUES (:user_id, :type, :message, NOW(), 0)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
        ]);
    }

    /**
     * Dispatch the appropriate notification method
     */
    private function dispatchNotification(int $userId, string $type, string $message, array $options): bool
    {
        return match ($type) {
            'email' => $this->sendEmail($options['email'] ?? '', $message, $options['subject'] ?? 'Notification'),
            'sms' => $this->sendSMS($options['phone'] ?? '', $message),
            'webhook' => $this->sendWebhook($options['url'] ?? '', $message),
            'push' => $this->sendPushNotification($options['device_token'] ?? '', $message),
            default => throw new \InvalidArgumentException("Unsupported notification type: $type"),
        };
    }

    /**
     * Send an email using PHPMailer
     */
    private function sendEmail(string $to, string $message, string $subject): bool
    {
        if (empty($to)) return false;

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_user'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_secure'] ?? 'tls';
            $mail->Port = $this->config['smtp_port'];
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = "<p>$message</p>";
            $mail->send();

            return true;
        } catch (Exception $e) {
            $this->logger->error('Email failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send an SMS
     */
    private function sendSMS(string $phone, string $message): bool
    {
        if (empty($phone)) return false;

        try {
            $this->logger->info("Sending SMS to $phone: $message");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('SMS failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send a webhook notification
     */
    private function sendWebhook(string $url, string $message): bool
    {
        if (empty($url)) return false;

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $message]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $response = curl_exec($ch);
            curl_close($ch);

            return $response !== false;
        } catch (\Exception $e) {
            $this->logger->error('Webhook failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send a push notification
     */
    private function sendPushNotification(string $deviceToken, string $message): bool
    {
        if (empty($deviceToken)) return false;

        try {
            $payload = [
                'to' => $deviceToken,
                'notification' => ['title' => 'Notification', 'body' => $message],
            ];
            return $this->sendFCMRequest($payload);
        } catch (\Exception $e) {
            $this->logger->error('Push notification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send Firebase Cloud Messaging (FCM) request
     */
    private function sendFCMRequest(array $payload): bool
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

        curl_close($ch);
        return $response !== false;
    }
}
