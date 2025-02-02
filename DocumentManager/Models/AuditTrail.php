<?php

namespace AuditManager\Models;

use PDO;

/**
 * AuditTrail Model
 *
 * Represents the audit trails stored in the database and provides methods
 * for accessing and filtering the logs.
 */
class AuditTrail
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new audit trail record.
     *
     * @param string $action The action performed (e.g., 'user_registered').
     * @param string $details Additional details about the action.
     * @param int|null $userId The ID of the user performing the action.
     * @param int|null $bookingId The ID of the related booking (if applicable).
     * @param string|null $ipAddress The IP address of the user.
     * @return bool True on success, false otherwise.
     */
    public function create(
        string $action,
        string $details,
        ?int $userId = null,
        ?int $bookingId = null,
        ?string $ipAddress = null
    ): bool {
        $query = "
            INSERT INTO audit_trails (action, details, user_id, booking_id, ip_address, created_at)
            VALUES (:action, :details, :user_id, :booking_id, :ip_address, NOW())
        ";

        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            ':action' => $action,
            ':details' => $details,
            ':user_id' => $userId,
            ':booking_id' => $bookingId,
            ':ip_address' => $ipAddress,
        ]);
    }

    /**
     * Retrieve audit trail records based on filters.
     *
     * @param array $filters An associative array of filters:
     *                       - 'user_id' (int): Filter by user ID.
     *                       - 'booking_id' (int): Filter by booking ID.
     *                       - 'action' (string): Filter by action type.
     *                       - 'start_date' (string): Filter by start date (YYYY-MM-DD).
     *                       - 'end_date' (string): Filter by end date (YYYY-MM-DD).
     * @return array An array of matching audit trail records.
     */
    public function getLogs(array $filters = []): array
    {
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
    }

    /**
     * Delete an audit trail record by ID.
     *
     * @param int $id The ID of the audit trail record to delete.
     * @return bool True on success, false otherwise.
     */
    public function deleteById(int $id): bool
    {
        $query = "DELETE FROM audit_trails WHERE id = :id";
        $stmt = $this->db->prepare($query);

        return $stmt->execute([':id' => $id]);
    }
}
