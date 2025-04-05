<?php

namespace App\Controllers;

use App\Services\NotificationService;
use App\Services\AuthService;
use App\Helpers\JsonResponse;
use App\Services\Auth\TokenService;
use App\Helpers\ExceptionHandler;
use App\Services\AuditService;
use App\Services\RateLimiterService;
use App\Services\WebSocketService;
use App\Services\NotificationQueueService;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

require_once 'ViewHelper.php';

/**
 * Notification Controller
 *
 * Handles notification management, including sending notifications,
 * marking notifications as read, deleting notifications, fetching user notifications,
 * and managing notification preferences.
 * 
 * @api {get} /notifications View user notifications (HTML response)
 * @api {get} /notifications/user Get user notifications (JSON response)
 * @api {get} /notifications/unread Fetch unread notifications
 * @api {post} /notifications/mark-read Mark notification as read
 * @api {post} /notifications/delete Delete a notification
 * @api {post} /notifications/send Send a notification (admin only)
 * @api {get} /notifications/preferences Get user notification preferences
 * @api {put} /notifications/preferences Update user notification preferences
 */
class NotificationController extends Controller
{
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;
    private AuditService $auditService;
    private TokenService $tokenService;
    private NotificationService $notificationService;
    private RateLimiterService $rateLimiter;
    private WebSocketService $webSocketService;
    private NotificationQueueService $notificationQueue;
    
    /** @var array Valid notification types */
    private const VALID_NOTIFICATION_TYPES = ['email', 'sms', 'push', 'in_app', 'webhook'];
    
    /** @var array Valid notification priorities */
    private const VALID_PRIORITIES = ['low', 'normal', 'high'];
    
    /** @var int Default items per page */
    private const DEFAULT_PER_PAGE = 20;
    
    /** @var int Maximum items per page */
    private const MAX_PER_PAGE = 100;

    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService,
        TokenService $tokenService,
        NotificationService $notificationService,
        RateLimiterService $rateLimiter,
        WebSocketService $webSocketService,
        NotificationQueueService $notificationQueue
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
        $this->tokenService = $tokenService;
        $this->notificationService = $notificationService;
        $this->rateLimiter = $rateLimiter;
        $this->webSocketService = $webSocketService;
        $this->notificationQueue = $notificationQueue;
    }

    /**
     * Display user notifications.
     * 
     * @return ResponseInterface HTML response for UI integration
     */
    public function viewNotifications(): ResponseInterface
    {
        try {
            // Use TokenService to validate the request and get the user
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'UNAUTHORIZED',
                    'message' => 'Invalid token or unauthorized access'
                ], 401);
            }
            
            $userId = $user['id'];
            
            // Get pagination parameters
            $page = (int)($this->request->getQueryParams()['page'] ?? 1);
            $perPage = (int)($this->request->getQueryParams()['per_page'] ?? 10);
            
            // Validate pagination parameters
            $page = max(1, $page);
            $perPage = min(50, max(1, $perPage));
            
            $notifications = $this->notificationService->getUserNotifications(
                $userId, 
                ['page' => $page, 'per_page' => $perPage]
            );
            
            // Get pagination metadata
            $totalCount = $this->notificationService->countUserNotifications($userId);
            $totalPages = ceil($totalCount / $perPage);
            $unreadCount = $this->notificationService->countUnreadNotifications($userId);
                
            // Log notification view in audit logs
            $this->auditService->logEvent(
                'notifications_viewed',
                "User viewed their notifications",
                ['user_id' => $userId, 'page' => $page, 'per_page' => $perPage],
                $userId,
                null,
                'notification'
            );
            
            // Mark notifications as seen (not read)
            $this->notificationService->markNotificationsAsSeen($notifications);
                
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Notifications loaded',
                'data'    => ['notifications' => $notifications],
                'meta'    => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalCount,
                    'per_page' => $perPage,
                    'unread_count' => $unreadCount
                ]
            ], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status'  => 'error',
                'code'    => 'SERVER_ERROR',
                'message' => 'An error occurred while fetching notifications'
            ], 500);
        }
    }

    /**
     * Fetch all notifications for the authenticated user.
     * 
     * @return ResponseInterface JSON response with notifications
     */
    public function getUserNotifications(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'UNAUTHORIZED',
                    'message' => 'Invalid token'
                ], 401);
            }

            // Get query parameters for filtering
            $queryParams = $this->request->getQueryParams();
            $page = (int)($queryParams['page'] ?? 1);
            $perPage = (int)($queryParams['per_page'] ?? self::DEFAULT_PER_PAGE);
            $perPage = min($perPage, self::MAX_PER_PAGE);
            
            // Get filter parameters
            $filters = [
                'read' => isset($queryParams['read']) ? filter_var($queryParams['read'], FILTER_VALIDATE_BOOLEAN) : null,
                'type' => $queryParams['type'] ?? null,
                'date_from' => $queryParams['date_from'] ?? null,
                'date_to' => $queryParams['date_to'] ?? null
            ];
            
            // Pagination parameters
            $pagination = [
                'page' => $page,
                'per_page' => $perPage
            ];
            
            $notifications = $this->notificationService->getUserNotifications($user['id'], $pagination, $filters);
            
            // Get pagination metadata
            $totalCount = $this->notificationService->countUserNotifications($user['id'], $filters);
            $totalPages = ceil($totalCount / $perPage);
            $unreadCount = $this->notificationService->countUnreadNotifications($user['id']);
                
            // Log in audit logs
            $this->auditService->logEvent(
                'user_notifications_fetched',
                "User fetched their notifications via API",
                [
                    'user_id' => $user['id'],
                    'filters' => $filters,
                    'pagination' => $pagination
                ],
                $user['id'],
                null,
                'notification'
            );
                
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Notifications retrieved successfully',
                'data' => ['notifications' => $notifications],
                'meta' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalCount,
                    'per_page' => $perPage,
                    'unread_count' => $unreadCount
                ]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'code'   => 'SERVER_ERROR',
                'message' => 'Failed to fetch user notifications'
            ], 500);
        }
    }

    /**
     * Fetch unread notifications via AJAX.
     * 
     * @return ResponseInterface JSON response with unread notifications
     */
    public function fetchNotificationsAjax(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'UNAUTHORIZED',
                    'message' => 'Invalid token'
                ], 401);
            }

            // Get limit from query params
            $limit = (int)($this->request->getQueryParams()['limit'] ?? 10);
            $limit = min(50, max(1, $limit));
            
            $notifications = $this->notificationService->getUnreadNotifications($user['id'], $limit);
            $notificationCount = $this->notificationService->countUnreadNotifications($user['id']);
                
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
                'data' => ['notifications' => $notifications],
                'meta' => ['total_unread' => $notificationCount]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'code'   => 'SERVER_ERROR',
                'message' => 'Failed to fetch notifications'
            ], 500);
        }
    }

    /**
     * Mark a notification as read.
     * 
     * @return ResponseInterface JSON response
     */
    public function markNotificationAsRead(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'UNAUTHORIZED',
                    'message' => 'Invalid token'
                ], 401);
            }

            $data = $this->validateRequest($_POST, [
                'notification_id' => 'required|integer'
            ]);
            
            if (!isset($data['notification_id'])) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'MISSING_NOTIFICATION_ID',
                    'message' => 'Notification ID is missing'
                ], 400);
            }

            // Verify ownership first using notification service
            $notification = $this->notificationService->verifyNotificationOwnership($data['notification_id'], $user['id']);
            if (!$notification) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'NOTIFICATION_NOT_FOUND',
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
            return $this->jsonResponse([
                'status' => 'error',
                'code'   => 'SERVER_ERROR',
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }

    /**
     * Delete a notification.
     * 
     * @return ResponseInterface JSON response
     */
    public function deleteNotification(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'UNAUTHORIZED',
                    'message' => 'Invalid token'
                ], 401);
            }

            $data = $this->validateRequest($_POST, [
                'notification_id' => 'required|integer'
            ]);
            
            if (!isset($data['notification_id'])) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'MISSING_NOTIFICATION_ID',
                    'message' => 'Notification ID is missing'
                ], 400);
            }

            // Verify ownership first using notification service
            $notification = $this->notificationService->verifyNotificationOwnership($data['notification_id'], $user['id']);
            if (!$notification) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'NOTIFICATION_NOT_FOUND',
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
            return $this->jsonResponse([
                'status' => 'error',
                'code'   => 'SERVER_ERROR',
                'message' => 'Failed to delete notification'
            ], 500);
        }
    }

    /**
     * Send a notification to a specific user or group of users.
     * Admin only functionality.
     * 
     * @return ResponseInterface JSON response
     */
    public function sendNotification(): ResponseInterface
    {
        try {
            // Check admin privileges
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'UNAUTHORIZED',
                    'message' => 'Invalid token'
                ], 401);
            }
            
            // Check if user has admin role
            if (!$this->tokenService->hasRole($user, 'admin')) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'FORBIDDEN',
                    'message' => 'Admin privileges required'
                ], 403);
            }
            
            // Apply rate limiting
            if (!$this->rateLimiter->check('send_notification', $user['id'], 100)) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Rate limit exceeded. Try again later.'
                ], 429);
            }

            $postData = $this->request->getParsedBody() ?? [];
            
            // Validate user_id or user_ids is provided
            if (!isset($postData['user_id']) && !isset($postData['user_ids'])) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'VALIDATION_ERROR',
                    'message' => 'Either user_id or user_ids must be provided'
                ], 400);
            }
            
            // Validate request data
            $data = $this->validateRequest($postData, [
                'user_id' => 'nullable|integer',
                'user_ids' => 'nullable|array',
                'type' => 'required|string|in:booking_confirmation,payment_processed,system_notification',
                'message' => 'required|string|max:500',
                'data' => 'nullable|array',
                'send_email' => 'nullable|boolean',
                'send_sms' => 'nullable|boolean',
                'priority' => 'nullable|string|in:low,normal,high'
            ]);
            
            // Get recipient users
            $userIds = [];
            if (isset($data['user_id'])) {
                $userIds[] = $data['user_id'];
            } elseif (isset($data['user_ids'])) {
                $userIds = $data['user_ids'];
            }
            
            // Validate users exist
            foreach ($userIds as $userId) {
                if (!$this->notificationService->userExists($userId)) {
                    return $this->jsonResponse([
                        'status' => 'error',
                        'code'   => 'USER_NOT_FOUND',
                        'message' => 'One or more recipient users not found'
                    ], 404);
                }
            }
            
            $options = [
                'data' => $data['data'] ?? [],
                'send_email' => $data['send_email'] ?? false,
                'send_sms' => $data['send_sms'] ?? false,
                'priority' => $data['priority'] ?? 'normal'
            ];
            
            // Use queue service for bulk notifications
            if (count($userIds) > 10) {
                $this->notificationQueue->queueNotifications(
                    $userIds,
                    $data['type'],
                    $data['message'],
                    $options
                );
                
                $this->auditService->logEvent(
                    'notification_bulk_queued',
                    "Bulk notifications queued",
                    [
                        'user_count' => count($userIds),
                        'type' => $data['type'],
                        'sender_id' => $user['id']
                    ],
                    $user['id'],
                    null,
                    'notification'
                );
                
                return $this->jsonResponse([
                    'status' => 'success',
                    'message' => 'Bulk notifications queued for processing',
                    'data' => [
                        'queued_count' => count($userIds)
                    ]
                ], 202);
            }
            
            $sentNotifications = [];
            $channelsSent = ['in_app'];
            
            // Send to each user
            foreach ($userIds as $recipientId) {
                // Use service to send notification
                $notificationId = $this->notificationService->sendNotification(
                    $recipientId,
                    $data['type'],
                    $data['message'],
                    $options
                );
                
                if ($notificationId) {
                    $sentNotifications[] = $notificationId;
                    
                    // Real-time notification via WebSocket if available
                    $this->webSocketService->sendToUser(
                        $recipientId,
                        'new_notification',
                        ['notification_id' => $notificationId]
                    );
                    
                    // Track which channels were used
                    if ($options['send_email']) {
                        $channelsSent[] = 'email';
                    }
                    
                    if ($options['send_sms']) {
                        $channelsSent[] = 'sms';
                    }
                }
            }
            
            if (empty($sentNotifications)) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'NOTIFICATION_FAILED',
                    'message' => 'Failed to send notification'
                ], 500);
            }
            
            // Log in audit logs
            $this->auditService->logEvent(
                'notification_sent',
                "User sent a notification",
                [
                    'recipient_count' => count($userIds),
                    'type' => $data['type'],
                    'channels' => $channelsSent
                ],
                $user['id'],
                null,
                'notification'
            );
            
            // Get details of the first notification for response
            $notificationDetails = null;
            if (!empty($sentNotifications)) {
                $notificationDetails = $this->notificationService->getNotificationById($sentNotifications[0]);
            }
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Notification sent successfully',
                'data' => [
                    'notification_id' => $sentNotifications[0] ?? null,
                    'notification' => $notificationDetails,
                    'channels_sent' => array_values(array_unique($channelsSent)),
                    'total_sent' => count($sentNotifications)
                ]
            ], 201);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'code'   => 'SERVER_ERROR',
                'message' => 'Failed to send notification'
            ], 500);
        }
    }
    
    /**
     * Get notification preferences for the authenticated user.
     * 
     * @return ResponseInterface JSON response with preferences
     */
    public function getNotificationPreferences(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'UNAUTHORIZED',
                    'message' => 'Invalid token'
                ], 401);
            }
            
            $preferences = $this->notificationService->getUserPreferences($user['id']);
            
            // Log preferences retrieval
            $this->auditService->logEvent(
                'notification_preferences_viewed',
                "User viewed notification preferences",
                ['user_id' => $user['id']],
                $user['id'],
                null,
                'notification'
            );
            
            return $this->jsonResponse([
                'status' => 'success',
                'data' => [
                    'preferences' => $preferences
                ]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'code'   => 'SERVER_ERROR',
                'message' => 'Failed to retrieve notification preferences'
            ], 500);
        }
    }
    
    /**
     * Update notification preferences for the authenticated user.
     * 
     * @return ResponseInterface JSON response
     */
    public function updateNotificationPreferences(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateRequest($this->request);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'UNAUTHORIZED',
                    'message' => 'Invalid token'
                ], 401);
            }
            
            $input = $this->request->getParsedBody() ?? [];
            
            // Validate basic preference fields
            $data = [];
            
            if (isset($input['email_enabled'])) {
                $data['email_enabled'] = filter_var($input['email_enabled'], FILTER_VALIDATE_BOOLEAN);
            }
            
            if (isset($input['sms_enabled'])) {
                $data['sms_enabled'] = filter_var($input['sms_enabled'], FILTER_VALIDATE_BOOLEAN);
            }
            
            if (isset($input['push_enabled'])) {
                $data['push_enabled'] = filter_var($input['push_enabled'], FILTER_VALIDATE_BOOLEAN);
            }
            
            // Validate categories if provided
            if (isset($input['categories']) && is_array($input['categories'])) {
                $categories = [];
                $validCategories = ['booking', 'payment', 'system'];
                $validChannels = ['in_app', 'email', 'sms', 'push'];
                
                foreach ($input['categories'] as $category => $settings) {
                    // Skip if not a valid category
                    if (!in_array($category, $validCategories)) {
                        continue;
                    }
                    
                    $categorySettings = [];
                    foreach ($validChannels as $channel) {
                        if (isset($settings[$channel])) {
                            $categorySettings[$channel] = filter_var($settings[$channel], FILTER_VALIDATE_BOOLEAN);
                        }
                    }
                    
                    if (!empty($categorySettings)) {
                        $categories[$category] = $categorySettings;
                    }
                }
                
                if (!empty($categories)) {
                    $data['categories'] = $categories;
                }
            }
            
            // Validate quiet hours
            if (isset($input['quiet_hours']) && is_array($input['quiet_hours'])) {
                $quietHours = [];
                
                if (isset($input['quiet_hours']['enabled'])) {
                    $quietHours['enabled'] = filter_var($input['quiet_hours']['enabled'], FILTER_VALIDATE_BOOLEAN);
                }
                
                if (isset($input['quiet_hours']['start']) && preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $input['quiet_hours']['start'])) {
                    $quietHours['start'] = $input['quiet_hours']['start'];
                }
                
                if (isset($input['quiet_hours']['end']) && preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $input['quiet_hours']['end'])) {
                    $quietHours['end'] = $input['quiet_hours']['end'];
                }
                
                if (isset($input['quiet_hours']['timezone'])) {
                    try {
                        new \DateTimeZone($input['quiet_hours']['timezone']);
                        $quietHours['timezone'] = $input['quiet_hours']['timezone'];
                    } catch (\Exception $e) {
                        return $this->jsonResponse([
                            'status' => 'error',
                            'code'   => 'VALIDATION_ERROR',
                            'message' => 'Invalid timezone specified'
                        ], 400);
                    }
                }
                
                if (!empty($quietHours)) {
                    $data['quiet_hours'] = $quietHours;
                }
            }
            
            if (empty($data)) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'VALIDATION_ERROR',
                    'message' => 'No valid preferences provided'
                ], 400);
            }
            
            $success = $this->notificationService->updateUserPreferences($user['id'], $data);
            
            if (!$success) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'code'   => 'UPDATE_FAILED',
                    'message' => 'Failed to update notification preferences'
                ], 500);
            }
            
            // Log preferences update
            $this->auditService->logEvent(
                'notification_preferences_updated',
                "User updated notification preferences",
                ['user_id' => $user['id'], 'changes' => $data],
                $user['id'],
                null,
                'notification'
            );
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Notification preferences updated successfully'
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'code'   => 'SERVER_ERROR',
                'message' => 'Failed to update notification preferences'
            ], 500);
        }
    }
}
