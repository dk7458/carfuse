<?php

namespace DocumentManager\Models;

use PDO;
use Exception;

/**
 * Document Model
 *
 * Represents documents stored in the system and provides methods
 * for managing and querying them.
 */
class Document
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new document record.
     *
     * @param string $name The name of the document.
     * @param string $filePath The file path of the stored document.
     * @param int|null $userId The ID of the user associated with the document (if applicable).
     * @param string|null $type The type of document (e.g., 'contract', 'terms').
     * @return int The ID of the newly created document.
     * @throws Exception If the document creation fails.
     */
    public function create(string $name, string $filePath, ?int $userId = null, ?string $type = null): int
    {
        $query = "
            INSERT INTO documents (name, file_path, user_id, type, created_at)
            VALUES (:name, :file_path, :user_id, :type, NOW())
        ";

        $stmt = $this->db->prepare($query);

        if (!$stmt->execute([
            ':name' => $name,
            ':file_path' => $filePath,
            ':user_id' => $userId,
            ':type' => $type,
        ])) {
            throw new Exception('Failed to create document.');
        }

        return (int)$this->db->lastInsertId();
    }

    /**
     * Retrieve a document by its ID.
     *
     * @param int $id The ID of the document.
     * @return array|null The document record or null if not found.
     */
    public function getById(int $id): ?array
    {
        $query = "SELECT * FROM documents WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);

        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        return $document ?: null;
    }

    /**
     * Retrieve documents associated with a user.
     *
     * @param int $userId The ID of the user.
     * @return array A list of documents associated with the user.
     */
    public function getByUserId(int $userId): array
    {
        $query = "SELECT * FROM documents WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve documents by type.
     *
     * @param string $type The type of document (e.g., 'contract', 'terms').
     * @return array A list of documents matching the specified type.
     */
    public function getByType(string $type): array
    {
        $query = "SELECT * FROM documents WHERE type = :type ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':type' => $type]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete a document by its ID.
     *
     * @param int $id The ID of the document to delete.
     * @return bool True on success, false otherwise.
     */
    public function deleteById(int $id): bool
    {
        $query = "DELETE FROM documents WHERE id = :id";
        $stmt = $this->db->prepare($query);

        return $stmt->execute([':id' => $id]);
    }
}
