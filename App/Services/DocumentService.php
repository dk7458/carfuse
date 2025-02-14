<?php

namespace DocumentManager\Services;

use PDO;
use Exception;
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
    private PDO $db;
    private AuditService $auditService;
    private FileStorage $fileStorage;
    private EncryptionService $encryptionService;
    private TemplateService $templateService;
    private LoggerInterface $logger;

    public function __construct(
        PDO $db,
        AuditService $auditService,
        FileStorage $fileStorage,
        EncryptionService $encryptionService,
        TemplateService $templateService,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->auditService = $auditService;
        $this->fileStorage = $fileStorage;
        $this->encryptionService = $encryptionService;
        $this->templateService = $templateService;
        $this->logger = $logger;
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
            $this->logger->info("Uploading template: {$name}");
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
            $this->logger->info("Generating contract", ['bookingId' => $bookingId, 'userId' => $userId]);

            $templateContent = $this->templateService->loadTemplate('rental_contract.html');
            $data = array_merge($this->fetchUserData($userId), $this->fetchBookingData($bookingId));
            $renderedContent = $this->templateService->renderTemplate('rental_contract.html', $data);

            $encryptedContract = $this->encryptionService->encrypt($renderedContent);
            $filePath = $this->fileStorage->storeFile("contracts", "contract_{$bookingId}.pdf", $encryptedContract);

            $this->db->prepare("
                INSERT INTO contracts (booking_id, user_id, contract_pdf, created_at) 
                VALUES (:booking_id, :user_id, :contract_pdf, NOW())
            ")->execute(['booking_id' => $bookingId, 'user_id' => $userId, 'contract_pdf' => $filePath]);

            $this->auditService->log('contract_generated', ['booking_id' => $bookingId, 'user_id' => $userId]);

            return $filePath;
        } catch (Exception $e) {
            $this->handleException("Failed to generate contract", $e);
        }
    }

    /**
     * Retrieve and decrypt a document.
     */
    public function retrieveDocument(string $filePath): string
    {
        try {
            $this->logger->info("Retrieving document", ['filePath' => $filePath]);

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
            $this->logger->info("Deleting document", ['documentId' => $documentId]);

            $stmt = $this->db->prepare("SELECT file_path FROM documents WHERE id = :document_id");
            $stmt->execute(['document_id' => $documentId]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                throw new Exception("Document not found.");
            }

            $this->fileStorage->deleteFile($document['file_path']);
            $this->db->prepare("DELETE FROM documents WHERE id = :document_id")
                ->execute(['document_id' => $documentId]);

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
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            throw new Exception($errorMessage);
        }

        return $record;
    }

    /**
     * Handle exceptions and log errors.
     */
    private function handleException(string $message, Exception $e): void
    {
        $this->logger->error($message, ['error' => $e->getMessage()]);
        throw new Exception($message . " " . $e->getMessage());
    }
}
