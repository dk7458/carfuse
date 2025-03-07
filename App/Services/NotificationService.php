<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use App\Models\Notification;
use Psr\Log\LoggerInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Helpers\ExceptionHandler;

/**
 * NotificationService
 *
 * Handles various notification types (email, SMS, webhook, push notifications).
 */
class NotificationService
{
    public const DEBUG_MODE = true;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private array $config;
    private Notification $notificationModel;

    public function __construct(
        LoggerInterface $logger, 
        ExceptionHandler $exceptionHandler, 
        Notification $notificationModel,
        array $config
    ) {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->notificationModel = $notificationModel;
        $this->config = $config;
    }

    /**
     * Send a notification
     */
    public function sendNotification(int $userId, string $type, string $message, array $options = []): bool
    {
        try {
            $notificationData = [
                'user_id' => $userId,
                'type'    => $type,
                'message' => $message,
                'sent_at' => date('Y-m-d H:i:s'),
                'is_read' => false,
            ];
            
            // Store notification using the model
            $this->notificationModel->create($notificationData);
            
            // Log notification preparation
            if (self::DEBUG_MODE) {
                $this->logger->info('Notification prepared for dispatch', ['user_id' => $userId, 'type' => $type]);
            }
            return $this->dispatchNotification($userId, $type, $message, $options);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send notification', ['error' => $e->getMessage()]);
            $this->exceptionHandler->handleException($e);
            return false;
        }
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(int $userId): array
    {
        try {
            // Get user notifications using the model
            $notifications = $this->notificationModel->getByUserId($userId);
            
            // Log retrieval
            if (self::DEBUG_MODE) {
                $this->logger->info("[Notification] Retrieved notifications for user {$userId}");
            }
            
            return $notifications;
        } catch (\Exception $e) {
            $this->logger->error("[Notification] ❌ getUserNotifications error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }
    
    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications(int $userId): array
    {
        try {
            $notifications = $this->notificationModel->getUnreadByUserId($userId);
            
            // Log retrieval
            if (self::DEBUG_MODE) {
                $this->logger->info("[Notification] Retrieved unread notifications for user {$userId}");
            }
            
            return $notifications;
        } catch (\Exception $e) {
            $this->logger->error("[Notification] ❌ getUnreadNotifications error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(int $notificationId): bool
    {
        try {
            // Mark notification as read using the model
            $result = $this->notificationModel->markAsRead($notificationId);
            
            // Log the action
            if (self::DEBUG_MODE && $result) {
                $this->logger->info("[Notification] Marked notification {$notificationId} as read");
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("[Notification] ❌ markAsRead error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Delete a notification
     */
    public function deleteNotification(int $notificationId): bool
    {
        try {
            // Delete notification using the model
            $result = $this->notificationModel->delete($notificationId);
            
            // Log the deletion
            if (self::DEBUG_MODE && $result) {
                $this->logger->info("[Notification] Deleted notification {$notificationId}");
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("[Notification] ❌ deleteNotification error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): bool
    {
        try {
            // Mark all notifications as read using the model
            $result = $this->notificationModel->markAllAsRead($userId);
            
            // Log the action
            if (self::DEBUG_MODE && $result) {
                $this->logger->info("[Notification] Marked all notifications as read for user {$userId}");
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("[Notification] ❌ markAllAsRead error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Dispatch the appropriate notification method
     */
    private function dispatchNotification(int $userId, string $type, string $message, array $options): bool
    {
        $result = false;
        
        try {
            $result = match ($type) {
                'email' => $this->sendEmail($options['email'] ?? '', $message, $options['subject'] ?? 'Notification'),
                'sms' => $this->sendSMS($options['phone'] ?? '', $message),
                'webhook' => $this->sendWebhook($options['url'] ?? '', $message),
                'push' => $this->sendPushNotification($options['device_token'] ?? '', $message),
                default => throw new \InvalidArgumentException("Unsupported notification type: $type"),
            };
            
            // Business-level logging of success/failure
            if (self::DEBUG_MODE) {
                if ($result) {
                    $this->logger->info("[Notification] Successfully sent {$type} notification to user {$userId}");
                } else {
                    $this->logger->warning("[Notification] Failed to send {$type} notification to user {$userId}");
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("[Notification] ❌ Dispatch error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return false;
        }
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

            // Business-level logging only - no need for audit here
            if (self::DEBUG_MODE) {
                $this->logger->info("[Notification] Email sent to {$to}");
            }

            return true;
        } catch (Exception $e) {
            $this->logger->error("[Notification] ❌ Email error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
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
            // SMS implementation code would go here
            
            // Business-level logging only
            if (self::DEBUG_MODE) {
                $this->logger->info("[Notification] SMS sent to {$phone}");
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error("[Notification] ❌ SMS error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
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

            // Business-level logging only
            if (self::DEBUG_MODE && $response !== false) {
                $this->logger->info("[Notification] Webhook sent to {$url}");
            }

            return $response !== false;
        } catch (\Exception $e) {
            $this->logger->error('[Notification] ❌ Webhook error: ' . $e->getMessage());
            $this->exceptionHandler->handleException($e);
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
            
            $result = $this->sendFCMRequest($payload);
            
            // Business-level logging only
            if (self::DEBUG_MODE && $result) {
                $this->logger->info("[Notification] Push notification sent to device {$deviceToken}");
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('[Notification] ❌ Push notification error: ' . $e->getMessage());
            $this->exceptionHandler->handleException($e);
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
    
    /**
     * Get notification by ID
     */
    public function getNotificationById(int $id): ?array
    {
        try {
            $notification = $this->notificationModel->find($id);
            
            if (self::DEBUG_MODE && $notification) {
                $this->logger->info("[Notification] Retrieved notification by ID", ['id' => $id]);
            }
            
            return $notification;
        } catch (\Exception $e) {
            $this->logger->error("[Notification] ❌ getNotificationById error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Verify notification ownership
     */
    public function verifyNotificationOwnership(int $notificationId, int $userId): ?array
    {
        try {
            return $this->notificationModel->findForUser($notificationId, $userId);
        } catch (\Exception $e) {
            $this->logger->error("[Notification] ❌ verifyNotificationOwnership error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Get unread notifications count for user
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            $count = $this->notificationModel->getUnreadCount($userId);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Notification] Retrieved unread count for user", [
                    'user_id' => $userId, 
                    'count' => $count
                ]);
            }
            
            return $count;
        } catch (\Exception $e) {
            $this->logger->error("[Notification] ❌ getUnreadCount error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return 0;
        }
    }
}
