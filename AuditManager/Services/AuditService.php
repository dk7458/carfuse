<?php

namespace AuditManager\Services;

use PDO;
use Exception;

/**
 * Audit Service
 *
 * Provides functionality for logging actions in the audit trail and retrieving logs
 * based on filters. Designed to ensure a robust and secure audit trail for the application.
 */
class AuditService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Log an action in the audit trail.
     *
     * @param string $action - The action performed (e.g., 'document_created', 'payment_processed').
     * @param array $details - An array of additional details about the action (e.g., 'document_id' => 123).
     * @param int|null $userId - The ID of the user performing the action (if applicable).
     * @param int|null $bookingId - The ID of the related booking (if applicable).
     * @param string|null $ipAddress - The IP address of the user (if available).
     * @throws Exception
     */
    public function log(
        string $action,
        array $details = [],
        ?int $userId = null,
        ?int $bookingId = null,
        ?string $ipAddress = null
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_trails (action, details, user_id, booking_id, ip_address, created_at)
                VALUES (:action, :details, :user_id, :booking_id, :ip_address, NOW())
            ");

            $stmt->execute([
                ':action' => $action,
                ':details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                ':user_id' => $userId,
                ':booking_id' => $bookingId,
                ':ip_address' => $ipAddress,
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to log action: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve logs based on filters.
     *
     * @param array $filters - Array of filters for querying logs.
     *                          Supported keys: user_id, booking_id, action, start_date, end_date.
     * @return array - Array of logs matching the filters.
     * @throws Exception
     */
    public function getLogs(array $filters): array
    {
        try {
            $query = "SELECT * FROM audit_trails WHERE 1=1";
            $params = [];

            if (!empty($filters['user_id'])) {
                $query .= " AND user_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }

            if (!empty($filters['booking_id'])) {
                $query .= " AND booking_id = :booking_id";
                $params[':booking_id'] = $filters['booking_id'];
            }

            if (!empty($filters['action'])) {
                $query .= " AND action = :action";
                $params[':action'] = $filters['action'];
            }

            if (!empty($filters['start_date'])) {
                $query .= " AND created_at >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $query .= " AND created_at <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            $query .= " ORDER BY created_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve logs: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a single log entry by ID.
     *
     * @param int $logId - The ID of the log entry to retrieve.
     * @return array - The log entry data.
     * @throws Exception
     */
    public function getLogById(int $logId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM audit_trails WHERE id = :log_id");
            $stmt->execute([':log_id' => $logId]);

            $log = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$log) {
                throw new Exception('Log entry not found.');
            }

            return $log;
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve log entry: ' . $e->getMessage());
        }
    }

    /**
     * Delete logs based on specific filters.
     *
     * @param array $filters - Array of filters for deleting logs.
     *                         Supported keys: user_id, booking_id, action, start_date, end_date.
     * @return int - Number of rows deleted.
     * @throws Exception
     */
    public function deleteLogs(array $filters): int
    {
        try {
            $query = "DELETE FROM audit_trails WHERE 1=1";
            $params = [];

            if (!empty($filters['user_id'])) {
                $query .= " AND user_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }

            if (!empty($filters['booking_id'])) {
                $query .= " AND booking_id = :booking_id";
                $params[':booking_id'] = $filters['booking_id'];
            }

            if (!empty($filters['action'])) {
                $query .= " AND action = :action";
                $params[':action'] = $filters['action'];
            }

            if (!empty($filters['start_date'])) {
                $query .= " AND created_at >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $query .= " AND created_at <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (Exception $e) {
            throw new Exception('Failed to delete logs: ' . $e->getMessage());
        }
    }
}
