<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Services\AuthService;
use App\Helpers\JsonResponse;
use App\Helpers\TokenValidator;
use App\Helpers\ExceptionHandler;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

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
    private ExceptionHandler $exceptionHandler;
    private AuditService $auditService;

    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService
    ) {
        parent::__construct($logger);
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
    }

    /**
     * Display user notifications.
     */
    public function viewNotifications(): ResponseInterface
    {
        try {
            $userId = AuthService::getUserIdFromToken();
            
            $notifications = Notification::with('user')
                ->where('user_id', $userId)
                ->latest()
                ->get();
                
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
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $notifications = Notification::with('user')
                ->where('user_id', $user->id)
                ->latest()
                ->get();
                
            // Log in audit logs
            $this->auditService->logEvent(
                'user_notifications_fetched',
                "User fetched their notifications via API",
                ['user_id' => $user->id],
                $user->id,
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
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $notifications = Notification::with('user')
                ->where('user_id', $user->id)
                ->where('is_read', false)
                ->latest()
                ->get();
                
            // Log notification fetch in audit logs
            $this->auditService->logEvent(
                'unread_notifications_fetched',
                "User fetched unread notifications",
                ['user_id' => $user->id, 'count' => $notifications->count()],
                $user->id,
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
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $data = $this->validateRequest($_POST, [
                'notification_id' => 'required|integer'
            ]);

            $notification = Notification::findOrFail($data['notification_id']);
            
            // Ensure user owns this notification
            if ($notification->user_id != $user->id) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }

            $notification->update(['is_read' => true]);
            
            // Log in audit logs
            $this->auditService->logEvent(
                'notification_marked_as_read',
                "User marked a notification as read",
                ['user_id' => $user->id, 'notification_id' => $data['notification_id']],
                $user->id,
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
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $data = $this->validateRequest($_POST, [
                'notification_id' => 'required|integer'
            ]);

            $notification = Notification::findOrFail($data['notification_id']);
            
            // Ensure user owns this notification
            if ($notification->user_id != $user->id) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }

            $notification->delete();
            
            // Log in audit logs
            $this->auditService->logEvent(
                'notification_deleted',
                "User deleted a notification",
                ['user_id' => $user->id, 'notification_id' => $data['notification_id']],
                $user->id,
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
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
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

            // Store notification via Eloquent
            $notification = Notification::create([
                'user_id' => $data['user_id'],
                'type'    => $data['type'],
                'message' => $data['message'],
                'sent_at' => date('Y-m-d H:i:s'),
                'is_read' => false,
            ]);
            
            // Log in audit logs
            $this->auditService->logEvent(
                'notification_sent',
                "User sent a notification",
                ['user_id' => $data['user_id'], 'notification_id' => $notification->id],
                $user->id,
                null,
                'notification'
            );
            
            // Optionally dispatch via queue or any external channel here.
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Notification sent successfully',
                'data' => ['notification' => $notification]
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
