<?php

namespace App\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

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
                ->where('user_id', $_SESSION['user_id'] ?? null)
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
        try {
            $notifications = Notification::with('user')
                ->where('user_id', $_SESSION['user_id'] ?? null)
                ->latest()
                ->get();
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Notifications fetched',
                'data'    => ['notifications' => $notifications]
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to fetch user notifications',
                'data'    => []
            ], 500);
        }
    }

    /**
     * Fetch unread notifications via AJAX.
     */
    public function fetchNotificationsAjax()
    {
        try {
            $notifications = Notification::with('user')
                ->where('user_id', $_SESSION['user_id'] ?? null)
                ->where('is_read', false)
                ->latest()
                ->get();
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Notifications fetched',
                'data'    => ['notifications' => $notifications]
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to fetch notifications',
                'data'    => []
            ], 500);
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationAsRead()
    {
        $data = $this->validateRequest($_POST, [
            'notification_id' => 'required|integer'
        ]);

        try {
            $notification = Notification::findOrFail($data['notification_id']);
            $notification->update(['is_read' => true]);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Notification marked as read',
                'data'    => []
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to mark notification as read',
                'data'    => []
            ], 500);
        }
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification()
    {
        $data = $this->validateRequest($_POST, [
            'notification_id' => 'required|integer'
        ]);

        try {
            $notification = Notification::findOrFail($data['notification_id']);
            $notification->delete();
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Notification deleted',
                'data'    => []
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to delete notification',
                'data'    => []
            ], 500);
        }
    }

    /**
     * Send a notification.
     */
    public function sendNotification()
    {
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
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Notification sent successfully',
                'data'    => ['notification' => $notification]
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to send notification',
                'data'    => []
            ], 500);
        }
    }
}
