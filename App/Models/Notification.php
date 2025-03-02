<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;

/**
 * Notification Model
 *
 * Represents a notification in the system.
 */
class Notification extends BaseModel
{
    protected $table = 'notifications';
    protected $resourceName = 'notification';
    protected $useTimestamps = false;  // We'll use sent_at instead of created_at
    protected $useSoftDeletes = false; // Notifications don't use soft deletes

    /**
     * Mark a notification as read.
     *
     * @param int $id
     * @return bool
     */
    public function markAsRead(int $id): bool
    {
        $result = $this->dbHelper->update($this->table, ['is_read' => 1], ['id' => $id]);
        
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'notification_read', [
                'id' => $id
            ]);
        }
        
        return $result;
    }

    /**
     * Create a new notification.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        // Set sent_at to now if not provided
        if (!isset($data['sent_at'])) {
            $data['sent_at'] = date('Y-m-d H:i:s');
        }
        
        // Default is_read to false if not provided
        if (!isset($data['is_read'])) {
            $data['is_read'] = 0;
        }
        
        return parent::create($data);
    }

    /**
     * Get notifications by user ID.
     *
     * @param int $userId
     * @return array
     */
    public function getByUserId(int $userId): array
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE user_id = :user_id
            ORDER BY sent_at DESC
        ";
        
        $notifications = $this->dbHelper->select($query, [':user_id' => $userId]);
        return $notifications ?: [];
    }

    /**
     * Get unread notifications for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getUnreadByUserId(int $userId): array
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE user_id = :user_id AND is_read = 0
            ORDER BY sent_at DESC
        ";
        
        $notifications = $this->dbHelper->select($query, [':user_id' => $userId]);
        return $notifications ?: [];
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsReadForUser(int $userId): bool
    {
        $result = $this->dbHelper->update($this->table, ['is_read' => 1], ['user_id' => $userId, 'is_read' => 0]);
        
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'all_notifications_read', [
                'user_id' => $userId
            ]);
        }
        
        return $result;
    }

    /**
     * Get the user associated with a notification.
     *
     * @param int $notificationId
     * @return array|null
     */
    public function getUser(int $notificationId): ?array
    {
        $notification = $this->find($notificationId);
        
        if (!$notification || !isset($notification['user_id'])) {
            return null;
        }
        
        $query = "SELECT * FROM users WHERE id = :user_id";
        $result = $this->dbHelper->select($query, [':user_id' => $notification['user_id']]);
        return $result[0] ?? null;
    }
}
