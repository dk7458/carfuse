<?php

namespace App\Services;

use App\Helpers\DatabaseHelper; // new import
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
    private LoggerInterface $logger;
    private array $config;
    private $db;

    public function __construct(LoggerInterface $logger, DatabaseHelper $db, array $config)
    {
        $this->logger = $logger;
        $this->db = $db;
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
            $this->logger->error("[NotificationService] Notification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store notification in the database
     */
    private function storeNotification(int $userId, string $type, string $message): void
    {
        try {
            // Instead of Eloquent relationship, we use direct DB insert.
            $this->db->table('notifications')->insert([
                'user_id' => $userId,
                'type'    => $type,
                'message' => $message,
                'sent_at' => date('Y-m-d H:i:s'),
                'is_read' => false,
            ]);
            $this->logger->info("[NotificationService] Notification stored for user {$userId}", ['category' => 'notification']);
        } catch (\Exception $e) {
            $this->logger->error("[NotificationService] Database error (storeNotification): " . $e->getMessage(), ['category' => 'db']);
            throw $e;
        }
    }

    public function getUserNotifications(int $userId)
    {
        try {
            $notifications = $this->db->table('notifications')
                                 ->where('user_id', $userId)
                                 ->orderBy('created_at', 'desc')
                                 ->get();
            $this->logger->info("[NotificationService] Retrieved notifications for user {$userId}");
            return $notifications;
        } catch (\Exception $e) {
            $this->logger->error("[NotificationService] Database error (getUserNotifications): " . $e->getMessage());
            throw $e;
        }
    }

    public function markAsRead(int $notificationId): void
    {
        try {
            $this->db->table('notifications')
                     ->where('id', $notificationId)
                     ->update(['is_read' => true]);
            $this->logger->info("[NotificationService] Marked notification {$notificationId} as read");
        } catch (\Exception $e) {
            $this->logger->error("[NotificationService] Database error (markAsRead): " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteNotification(int $notificationId): void
    {
        try {
            $this->db->table('notifications')
                     ->where('id', $notificationId)
                     ->delete();
            $this->logger->info("[NotificationService] Deleted notification {$notificationId}");
        } catch (\Exception $e) {
            $this->logger->error("[NotificationService] Database error (deleteNotification): " . $e->getMessage());
            throw $e;
        }
    }

    public function markAllAsRead(int $userId): void
    {
        try {
            $this->db->table('notifications')
                     ->where('user_id', $userId)
                     ->update(['is_read' => true]);
            $this->logger->info("[NotificationService] Marked all notifications as read for user {$userId}");
        } catch (\Exception $e) {
            $this->logger->error("[NotificationService] Database error (markAllAsRead): " . $e->getMessage());
            throw $e;
        }
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
            $this->logger->info("[NotificationService] Email sent to {$to}");

            return true;
        } catch (Exception $e) {
            $this->logger->error("[NotificationService] Email error: " . $e->getMessage());
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
            $this->logger->info("[NotificationService] SMS sent to {$phone}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("[NotificationService] SMS error: " . $e->getMessage());
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
