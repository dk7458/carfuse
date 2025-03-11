<?php

namespace App\Controllers;

use App\Services\NotificationService;
use App\Services\AuthService;
use App\Helpers\JsonResponse;
use App\Services\Auth\TokenService;
use App\Helpers\ExceptionHandler;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;

require_once   'ViewHelper.php';

/**
 * Notification Controller
 *
 * Handles notification management, including sending notifications,
 * marking notifications as read, deleting notifications, and
 * fetching user notifications for display.
 */
class NotificationController extends Controller
{
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;
    private AuditService $auditService;
    private TokenService $tokenService;
    private NotificationService $notificationService;

    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService,
        TokenService $tokenService,
        NotificationService $notificationService
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
        $this->tokenService = $tokenService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display user notifications.
     */
    public function viewNotifications(): ResponseInterface
    {
        try {
            // Use TokenService to validate the request and get the user
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'message' => 'Invalid token or unauthorized access'
                ], 401);
            }
            
            $userId = $user['id'];
            $notifications = $this->notificationService->getUserNotifications($userId);
                
            // Log notification view in audit logs
            $this->auditService->logEvent(
                'notifications_viewed',
                "User viewed their notifications",
                ['user_id' => $userId],
                $userId,
                null,
                'notification'
            );
                
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Notifications loaded',
                'data'    => ['notifications' => $notifications]
            ], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'An error occurred while fetching notifications'
            ], 500);
        }
    }

    /**
     * Fetch all notifications for the authenticated user.
     */
    public function getUserNotifications(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $notifications = $this->notificationService->getUserNotifications($user['id']);
                
            // Log in audit logs
            $this->auditService->logEvent(
                'user_notifications_fetched',
                "User fetched their notifications via API",
                ['user_id' => $user['id']],
                $user['id'],
                null,
                'notification'
            );
                
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Notifications retrieved successfully',
                'data' => ['notifications' => $notifications]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to fetch user notifications'
            ], 500);
        }
    }

    /**
     * Fetch unread notifications via AJAX.
     */
    public function fetchNotificationsAjax(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $notifications = $this->notificationService->getUnreadNotifications($user['id']);
            $notificationCount = count($notifications);
                
            // Log notification fetch in audit logs
            $this->auditService->logEvent(
                'unread_notifications_fetched',
                "User fetched unread notifications",
                ['user_id' => $user['id'], 'count' => $notificationCount],
                $user['id'],
                null,
                'notification'
            );
                
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Notifications retrieved successfully',
                'data' => ['notifications' => $notifications]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to fetch notifications'
            ], 500);
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationAsRead(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $data = $this->validateRequest($_POST, [
                'notification_id' => 'required|integer'
            ]);

            // Verify ownership first using notification service
            $notification = $this->notificationService->verifyNotificationOwnership($data['notification_id'], $user['id']);
            if (!$notification) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Notification not found or access denied'
                ], 404);
            }

            $result = $this->notificationService->markAsRead($data['notification_id']);
            
            // Log in audit logs
            $this->auditService->logEvent(
                'notification_marked_as_read',
                "User marked a notification as read",
                ['user_id' => $user['id'], 'notification_id' => $data['notification_id']],
                $user['id'],
                null,
                'notification'
            );
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $data = $this->validateRequest($_POST, [
                'notification_id' => 'required|integer'
            ]);

            // Verify ownership first using notification service
            $notification = $this->notificationService->verifyNotificationOwnership($data['notification_id'], $user['id']);
            if (!$notification) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Notification not found or access denied'
                ], 404);
            }

            $this->notificationService->deleteNotification($data['notification_id']);
            
            // Log in audit logs
            $this->auditService->logEvent(
                'notification_deleted',
                "User deleted a notification",
                ['user_id' => $user['id'], 'notification_id' => $data['notification_id']],
                $user['id'],
                null,
                'notification'
            );
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Notification deleted'
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to delete notification'
            ], 500);
        }
    }

    /**
     * Send a notification.
     */
    public function sendNotification(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $data = $this->validateRequest($_POST, [
                'user_id' => 'required|integer',
                'type'    => 'required|in:email,sms,webhook,push',
                'message' => 'required|string|max:1000',
                'options' => 'nullable|array',
            ]);

            $options = $data['options'] ?? [];
            
            // Use service to send notification
            $result = $this->notificationService->sendNotification(
                $data['user_id'], 
                $data['type'], 
                $data['message'], 
                $options
            );
            
            if (!$result) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Failed to send notification'
                ], 500);
            }
            
            // Log in audit logs
            $this->auditService->logEvent(
                'notification_sent',
                "User sent a notification",
                ['user_id' => $data['user_id'], 'type' => $data['type']],
                $user['id'],
                null,
                'notification'
            );
            
            // Get the recent notifications to return the newly created one
            $recentNotifications = $this->notificationService->getUserNotifications($data['user_id']);
            $latestNotification = !empty($recentNotifications) ? $recentNotifications[0] : null;
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Notification sent successfully',
                'data' => ['notification' => $latestNotification]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to send notification'
            ], 500);
        }
    }
}
