<?php

namespace App\Controllers;

use App\Services\NotificationService;
use App\Services\Validator;
use Psr\Log\LoggerInterface;

/**
 * Notification Controller
 *
 * Handles notification management, including sending notifications,
 * marking notifications as read, deleting notifications, and
 * fetching user notifications for display.
 */
class NotificationController
{
    private NotificationService $notificationService;
    private Validator $validator;
    private LoggerInterface $logger;

    public function __construct(
        NotificationService $notificationService,
        Validator $validator,
        LoggerInterface $logger
    ) {
        $this->notificationService = $notificationService;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * Display user notifications in the view.
     */
    public function viewNotifications(int $userId): void
    {
        try {
            $notifications = $this->notificationService->getUserNotifications($userId);
            require_once __DIR__ . '/../views/user/notifications.php';
        } catch (\Exception $e) {
            $this->logger->error('Failed to load notifications view', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo 'An error occurred while fetching notifications.';
        }
    }

    /**
     * Fetch all notifications for a user via API.
     */
    public function getUserNotifications(int $userId): array
    {
        try {
            $notifications = $this->notificationService->getUserNotifications($userId);
            return ['status' => 'success', 'notifications' => $notifications];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user notifications', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to fetch user notifications'];
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationAsRead(int $notificationId): array
    {
        try {
            $this->notificationService->markAsRead($notificationId);
            $this->logger->info("Notification marked as read", ['notification_id' => $notificationId]);

            return ['status' => 'success', 'message' => 'Notification marked as read'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark notification as read', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to mark notification as read'];
        }
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification(int $notificationId): array
    {
        try {
            $this->notificationService->deleteNotification($notificationId);
            $this->logger->info("Notification deleted", ['notification_id' => $notificationId]);

            return ['status' => 'success', 'message' => 'Notification deleted'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete notification', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to delete notification'];
        }
    }

    /**
     * Send a notification to a user.
     */
    public function sendNotification(array $data): array
    {
        $rules = [
            'user_id' => 'required|integer',
            'type' => 'required|in:email,sms,webhook,push',
            'message' => 'required|string|max:1000',
            'options' => 'array',
        ];

        if (!$this->validator->validate($data, $rules)) {
            $this->logger->warning('Notification validation failed', ['data' => $data]);
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $success = $this->notificationService->sendNotification(
                $data['user_id'],
                $data['type'],
                $data['message'],
                $data['options'] ?? []
            );

            if ($success) {
                $this->logger->info('Notification sent successfully', ['data' => $data]);
                return ['status' => 'success', 'message' => 'Notification sent successfully'];
            }

            return ['status' => 'error', 'message' => 'Notification delivery failed'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to send notification', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to send notification'];
        }
    }

    /**
     * Retry sending a notification.
     */
    public function retryNotification(array $data): array
    {
        $rules = [
            'user_id' => 'required|integer',
            'type' => 'required|in:email,sms,webhook,push',
            'message' => 'required|string|max:1000',
            'options' => 'array',
        ];

        if (!$this->validator->validate($data, $rules)) {
            $this->logger->warning('Retry validation failed', ['data' => $data]);
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $success = $this->notificationService->sendNotificationWithRetry(
                $data['user_id'],
                $data['type'],
                $data['message'],
                $data['options'] ?? []
            );

            if ($success) {
                $this->logger->info('Notification retry succeeded', ['data' => $data]);
                return ['status' => 'success', 'message' => 'Notification sent successfully after retries'];
            }

            return ['status' => 'error', 'message' => 'Notification delivery failed after retries'];
        } catch (\Exception $e) {
            $this->logger->error('Retry failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Retry failed'];
        }
    }
}
