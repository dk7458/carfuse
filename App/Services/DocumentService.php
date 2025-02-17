<?php

namespace DocumentManager\Services;

use Exception;
use App\Helpers\DatabaseHelper; // added for database operations
use AuditManager\Services\AuditService;
use DocumentManager\Services\FileStorage;
use DocumentManager\Services\TemplateService;
use App\Services\EncryptionService;
use Psr\Log\LoggerInterface;

/**
 * Document Service
 *
 * Manages documents including templates, contracts, and Terms & Conditions (T&C).
 * Supports encryption, secure storage, logging, and dynamic document generation.
 */
class DocumentService
{
    private LoggerInterface $logger;
    private $db;
    private AuditService $auditService;
    private FileStorage $fileStorage;
    private EncryptionService $encryptionService;
    private TemplateService $templateService;

    public function __construct(
        LoggerInterface $logger,
        AuditService $auditService,
        FileStorage $fileStorage,
        EncryptionService $encryptionService,
        TemplateService $templateService
    ) {
        $this->logger = $logger;
        $this->db = DatabaseHelper::getInstance();
        $this->auditService = $auditService;
        $this->fileStorage = $fileStorage;
        $this->encryptionService = $encryptionService;
        $this->templateService = $templateService;
    }

    /**
     * Upload a document template.
     */
    public function uploadTemplate(string $name, string $content): void
    {
        $this->processTemplate($name, $content, 'template_uploaded');
    }

    /**
     * Upload the Terms & Conditions document.
     */
    public function uploadTerms(string $content): void
    {
        $this->processTemplate('terms_and_conditions', $content, 'terms_uploaded');
    }

    /**
     * Process template storage and logging.
     */
    private function processTemplate(string $name, string $content, string $logAction): void
    {
        try {
            $this->logger->info("Uploading template: {$name}", ['category' => 'document']);
            $encryptedContent = $this->encryptionService->encrypt($content);
            $this->templateService->saveTemplate("{$name}.html", $encryptedContent);
            $this->auditService->log($logAction, ['name' => $name]);
        } catch (Exception $e) {
            $this->handleException("Failed to upload template: {$name}", $e);
        }
    }

    /**
     * Generate a rental contract document dynamically.
     */
    public function generateContract(int $bookingId, int $userId): string
    {
        try {
            $this->logger->info("[DocumentService] Generating contract", ['bookingId' => $bookingId, 'userId' => $userId, 'category' => 'document']);

            $templateContent = $this->templateService->loadTemplate('rental_contract.html');
            $data = array_merge($this->fetchUserData($userId), $this->fetchBookingData($bookingId));
            $renderedContent = $this->templateService->renderTemplate('rental_contract.html', $data);

            $encryptedContract = $this->encryptionService->encrypt($renderedContent);
            $filePath = $this->fileStorage->storeFile("contracts", "contract_{$bookingId}.pdf", $encryptedContract);

            // Replace raw SQL insert/prepare with DatabaseHelper query
            $this->db->table('contracts')->insert([
                'booking_id'  => $bookingId,
                'user_id'     => $userId,
                'contract_pdf'=> $filePath,
                'created_at'  => now()
            ]);

            $this->auditService->log('contract_generated', ['booking_id' => $bookingId, 'user_id' => $userId]);

            return $filePath;
        } catch (Exception $e) {
            $this->logger->error("[DocumentService] Failed to generate contract: " . $e->getMessage(), ['category' => 'document']);
            throw $e;
        }
    }

    /**
     * Retrieve and decrypt a document.
     */
    public function retrieveDocument(string $filePath): string
    {
        try {
            $this->logger->info("Retrieving document", ['filePath' => $filePath, 'category' => 'document']);

            $encryptedContent = $this->fileStorage->retrieveFile($filePath);
            $decryptedContent = $this->encryptionService->decrypt($encryptedContent);

            $this->auditService->log('document_retrieved', ['file_path' => $filePath]);

            return $decryptedContent;
        } catch (Exception $e) {
            $this->handleException("Failed to retrieve document", $e);
        }
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(int $documentId): void
    {
        try {
            $this->logger->info("Deleting document", ['documentId' => $documentId, 'category' => 'document']);

            // Replace raw PDO prepare with DatabaseHelper query
            $document = $this->db->table('documents')->where('id', $documentId)->first();

            if (!$document) {
                throw new Exception("Document not found.");
            }

            $this->fileStorage->deleteFile($document->file_path);
            $this->db->table('documents')->where('id', $documentId)->delete();

            $this->auditService->log('document_deleted', ['document_id' => $documentId]);
        } catch (Exception $e) {
            $this->handleException("Failed to delete document", $e);
        }
    }

    /**
     * Fetch user data.
     */
    private function fetchUserData(int $userId): array
    {
        return $this->fetchRecord("SELECT * FROM users WHERE id = :id", ['id' => $userId], "User not found.");
    }

    /**
     * Fetch booking data.
     */
    private function fetchBookingData(int $bookingId): array
    {
        return $this->fetchRecord("SELECT * FROM bookings WHERE id = :id", ['id' => $bookingId], "Booking not found.");
    }

    /**
     * Fetch a record from the database.
     */
    private function fetchRecord(string $query, array $params, string $errorMessage): array
    {
        try {
            // Replace raw PDO query with DatabaseHelper call (assuming a helper method exists)
            $record = $this->db->table(explode(' ', $query)[3])
                               ->where(key($params), current($params))
                               ->first();
            if (!$record) {
                throw new Exception($errorMessage);
            }
            return (array)$record;
        } catch (Exception $e) {
            $this->logger->error("[DocumentService] Database error: " . $e->getMessage(), ['category' => 'database']);
            throw $e;
        }
    }

    /**
     * Handle exceptions and log errors.
     */
    private function handleException(string $message, Exception $e): void
    {
        $this->logger->error($message, ['error' => $e->getMessage(), 'category' => 'document']);
        throw new Exception($message . " " . $e->getMessage());
    }
}
