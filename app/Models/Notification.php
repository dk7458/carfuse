<?php

namespace App\Models;

use DateTime;
use PDO;
use PDOException;

/**
 * Notification Model
 *
 * Represents a notification in the system.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $message
 * @property DateTime $sent_at
 * @property bool $is_read
 */
class Notification
{
    private int $id;
    private int $user_id;
    private string $type;
    private string $message;
    private DateTime $sent_at;
    private bool $is_read;

    public function __construct(
        int $user_id,
        string $type,
        string $message,
        DateTime $sent_at = null,
        bool $is_read = false
    ) {
        $this->user_id = $user_id;
        $this->type = $type;
        $this->message = $message;
        $this->sent_at = $sent_at ?? new DateTime();
        $this->is_read = $is_read;
    }

    /**
     * Save the notification to the database.
     */
    public function save(PDO $db): bool
    {
        try {
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, message, sent_at, is_read)
                VALUES (:user_id, :type, :message, :sent_at, :is_read)
            ");
            $success = $stmt->execute([
                ':user_id' => $this->user_id,
                ':type' => $this->type,
                ':message' => $this->message,
                ':sent_at' => $this->sent_at->format('Y-m-d H:i:s'),
                ':is_read' => (int)$this->is_read,
            ]);

            if ($success) {
                $this->id = (int)$db->lastInsertId();
            }

            return $success;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(PDO $db): bool
    {
        try {
            $stmt = $db->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE id = :id
            ");
            return $stmt->execute([':id' => $this->id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete the notification from the database.
     */
    public function delete(PDO $db): bool
    {
        try {
            $stmt = $db->prepare("
                DELETE FROM notifications
                WHERE id = :id
            ");
            return $stmt->execute([':id' => $this->id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Fetch all notifications for a specific user.
     */
    public static function getByUserId(PDO $db, int $user_id): array
    {
        try {
            $stmt = $db->prepare("
                SELECT * FROM notifications
                WHERE user_id = :user_id
                ORDER BY sent_at DESC
            ");
            $stmt->execute([':user_id' => $user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function ($data) {
                return new self(
                    $data['user_id'],
                    $data['type'],
                    $data['message'],
                    new DateTime($data['sent_at']),
                    (bool)$data['is_read']
                );
            }, $results);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Fetch a single notification by its ID.
     */
    public static function getById(PDO $db, int $id): ?self
    {
        try {
            $stmt = $db->prepare("
                SELECT * FROM notifications
                WHERE id = :id
            ");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new self(
                    $data['user_id'],
                    $data['type'],
                    $data['message'],
                    new DateTime($data['sent_at']),
                    (bool)$data['is_read']
                );
            }

            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Mark all notifications as read for a user.
     */
    public static function markAllAsRead(PDO $db, int $user_id): bool
    {
        try {
            $stmt = $db->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE user_id = :user_id
            ");
            return $stmt->execute([':user_id' => $user_id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
