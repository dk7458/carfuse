<?php

namespace App\Controllers;

use App\Services\NotificationService;
use App\Services\Validator;
use Psr\Log\LoggerInterface;
use App\Queue\NotificationQueue;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

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
    private NotificationQueue $notificationQueue;

    public function __construct(
        NotificationService $notificationService,
        Validator $validator,
        LoggerInterface $logger,
        NotificationQueue $notificationQueue
    ) {
        $this->notificationService = $notificationService;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->notificationQueue = $notificationQueue;
    }

    /**
     * Display user notifications in the view.
     */
    public function viewNotifications(int $userId): void
    {
        try {
            $notifications = $this->notificationService->getUserNotifications($userId);
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Notifications loaded','data' => ['notifications' => $notifications]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to load notifications view', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'An error occurred while fetching notifications','data' => []]);
        }
        exit;
    }

    /**
     * Fetch all notifications for a user via API.
     */
    public function getUserNotifications(int $userId): array
    {
        try {
            $notifications = $this->notificationService->getUserNotifications($userId);
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Notifications fetched','data' => ['notifications' => $notifications]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to fetch user notifications', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to fetch user notifications','data' => []]);
        }
        exit;
    }

    /**
     * Fetch all notifications for a user via AJAX.
     */
    public function fetchNotificationsAjax(int $userId): void
    {
        try {
            $notifications = $this->notificationService->getUserNotifications($userId);
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Notifications fetched','data' => ['notifications' => $notifications]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to fetch user notifications via AJAX', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to fetch user notifications','data' => []]);
        }
        exit;
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationAsRead(int $notificationId): array
    {
        try {
            $this->notificationService->markAsRead($notificationId);
            $this->logger->info("Notification marked as read", ['notification_id' => $notificationId]);
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Notification marked as read','data' => []]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to mark notification as read', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to mark notification as read','data' => []]);
        }
        exit;
    }

    /**
     * Mark a notification as read via POST request.
     */
    public function markNotificationAsReadPost(): void
    {
        $notificationId = $_POST['notification_id'] ?? null;

        if (!$notificationId) {
            http_response_code(400);
            echo json_encode(['status' => 'error','message' => 'Notification ID is required','data' => []]);
            exit;
        }

        try {
            $this->notificationService->markAsRead((int)$notificationId);
            $this->logger->info("Notification marked as read", ['notification_id' => $notificationId]);
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Notification marked as read','data' => []]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to mark notification as read', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to mark notification as read','data' => []]);
        }
        exit;
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification(int $notificationId): array
    {
        try {
            $this->notificationService->deleteNotification($notificationId);
            $this->logger->info("Notification deleted", ['notification_id' => $notificationId]);
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Notification deleted','data' => []]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to delete notification', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to delete notification','data' => []]);
        }
        exit;
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
            http_response_code(400);
            echo json_encode(['status' => 'error','message' => 'Validation failed','data' => $this->validator->errors()]);
            exit;
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
                $this->notificationQueue->queueNotification($data);
                http_response_code(200);
                echo json_encode(['status' => 'success','message' => 'Notification sent successfully','data' => []]);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error','message' => 'Notification delivery failed','data' => []]);
            }
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to send notification', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to send notification','data' => []]);
        }
        exit;
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
            http_response_code(400);
            echo json_encode(['status' => 'error','message' => 'Validation failed','data' => $this->validator->errors()]);
            exit;
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
                http_response_code(200);
                echo json_encode(['status' => 'success','message' => 'Notification sent successfully after retries','data' => []]);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error','message' => 'Notification delivery failed after retries','data' => []]);
            }
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Retry failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Retry failed'];
        }
    }
}
