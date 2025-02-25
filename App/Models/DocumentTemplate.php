<?php

namespace DocumentManager\Models;

use PDO;
use Exception;

/**
 * DocumentTemplate Model
 *
 * Manages templates for documents such as contracts, invoices, and Terms & Conditions.
 */
class DocumentTemplate
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new document template.
     *
     * @param string $name The name of the template.
     * @param string $filePath The file path of the template.
     * @return int The ID of the newly created template.
     * @throws Exception If the template creation fails.
     */
    public function create(string $name, string $filePath): int
    {
        $query = "
            INSERT INTO document_templates (name, file_path, created_at)
            VALUES (:name, :file_path, NOW())
        ";

        $stmt = $this->db->prepare($query);

        if (!$stmt->execute([
            ':name' => $name,
            ':file_path' => $filePath,
        ])) {
            throw new Exception('Failed to create document template.');
        }

        return (int)$this->db->lastInsertId();
    }

    /**
     * Retrieve a template by its ID.
     *
     * @param int $id The ID of the template.
     * @return array|null The template record or null if not found.
     */
    public function getById(int $id): ?array
    {
        $query = "SELECT * FROM document_templates WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);

        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        return $template ?: null;
    }

    /**
     * Retrieve a template by its name.
     *
     * @param string $name The name of the template.
     * @return array|null The template record or null if not found.
     */
    public function getByName(string $name): ?array
    {
        $query = "SELECT * FROM document_templates WHERE name = :name";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':name' => $name]);

        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        return $template ?: null;
    }

    /**
     * Retrieve all templates.
     *
     * @return array A list of all document templates.
     */
    public function getAll(): array
    {
        $query = "SELECT * FROM document_templates ORDER BY created_at DESC";
        $stmt = $this->db->query($query);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete a template by its ID.
     *
     * @param int $id The ID of the template to delete.
     * @return bool True on success, false otherwise.
     */
    public function deleteById(int $id): bool
    {
        $query = "DELETE FROM document_templates WHERE id = :id";
        $stmt = $this->db->prepare($query);

        return $stmt->execute([':id' => $id]);
    }
}
