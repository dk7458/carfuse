<?php

namespace App\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use App\Services\AuthService;
use App\Helpers\JsonResponse;
use App\Helpers\TokenValidator;

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
    // Removed injected NotificationService, Validator, LoggerInterface, and NotificationQueue

    /**
     * Display user notifications.
     */
    public function viewNotifications()
    {
        try {
            $notifications = Notification::with('user')
                ->where('user_id', AuthService::getUserIdFromToken())
                ->latest()
                ->get();
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Notifications loaded',
                'data'    => ['notifications' => $notifications]
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'An error occurred while fetching notifications',
                'data'    => []
            ], 500);
        }
    }

    /**
     * Fetch all notifications for the authenticated user.
     */
    public function getUserNotifications()
    {
        $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$user) {
            return JsonResponse::unauthorized('Invalid token');
        }

        try {
            $notifications = Notification::with('user')
                ->where('user_id', AuthService::getUserIdFromToken())
                ->latest()
                ->get();
            return JsonResponse::success('Notifications retrieved successfully', $notifications);
        } catch (\Exception $e) {
            return JsonResponse::error('Failed to fetch user notifications', []);
        }
    }

    /**
     * Fetch unread notifications via AJAX.
     */
    public function fetchNotificationsAjax()
    {
        $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$user) {
            return JsonResponse::unauthorized('Invalid token');
        }

        try {
            $notifications = Notification::with('user')
                ->where('user_id', AuthService::getUserIdFromToken())
                ->where('is_read', false)
                ->latest()
                ->get();
            return JsonResponse::success('Notifications retrieved successfully', $notifications);
        } catch (\Exception $e) {
            return JsonResponse::error('Failed to fetch notifications', []);
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationAsRead()
    {
        $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$user) {
            return JsonResponse::unauthorized('Invalid token');
        }

        $data = $this->validateRequest($_POST, [
            'notification_id' => 'required|integer'
        ]);

        try {
            $notification = Notification::findOrFail($data['notification_id']);
            $notification->update(['is_read' => true]);
            return JsonResponse::success('Notification marked as read', []);
        } catch (\Exception $e) {
            return JsonResponse::error('Failed to mark notification as read', []);
        }
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification()
    {
        $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$user) {
            return JsonResponse::unauthorized('Invalid token');
        }

        $data = $this->validateRequest($_POST, [
            'notification_id' => 'required|integer'
        ]);

        try {
            $notification = Notification::findOrFail($data['notification_id']);
            $notification->delete();
            return JsonResponse::success('Notification deleted', []);
        } catch (\Exception $e) {
            return JsonResponse::error('Failed to delete notification', []);
        }
    }

    /**
     * Send a notification.
     */
    public function sendNotification()
    {
        $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$user) {
            return JsonResponse::unauthorized('Invalid token');
        }

        $data = $this->validateRequest($_POST, [
            'user_id' => 'required|integer',
            'type'    => 'required|in:email,sms,webhook,push',
            'message' => 'required|string|max:1000',
            'options' => 'nullable|array',
        ]);

        try {
            // Store notification via Eloquent
            $notification = Notification::create([
                'user_id' => $data['user_id'],
                'type'    => $data['type'],
                'message' => $data['message'],
                'sent_at' => date('Y-m-d H:i:s'),
                'is_read' => false,
            ]);
            // Optionally dispatch via queue or any external channel here.
            return JsonResponse::success('Notification sent successfully', ['notification' => $notification]);
        } catch (\Exception $e) {
            return JsonResponse::error('Failed to send notification', []);
        }
    }
}
