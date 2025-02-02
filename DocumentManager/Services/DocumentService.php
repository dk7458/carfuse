<?php

namespace DocumentManager\Services;

use PDO;
use Exception;
use AuditManager\Services\AuditService;
use DocumentManager\Services\FileStorage;
use DocumentManager\Services\TemplateService;
use DocumentManager\Services\EncryptionService;
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
    public function uploadTemplate(string $name, string $content): int
    {
        try {
            $this->logger->info('Uploading document template', ['name' => $name]);

            // Encrypt the content
            $encryptedContent = $this->encryptionService->encrypt($content);

            // Save the template
            $this->templateService->saveTemplate($name . '.html', $encryptedContent);

            // Log the action
            $this->auditService->log('template_uploaded', ['name' => $name]);

            return 1; // Placeholder for additional logic if needed
        } catch (Exception $e) {
            $this->logger->error('Failed to upload template', ['error' => $e->getMessage()]);
            throw new Exception("Failed to upload template: " . $e->getMessage());
        }
    }

    /**
     * Generate a rental contract document dynamically.
     */
    public function generateContract(int $bookingId, int $userId): string
    {
        try {
            $this->logger->info('Generating contract', ['bookingId' => $bookingId, 'userId' => $userId]);

            // Load the rental contract template
            $templateName = 'rental_contract.html';
            $templateContent = $this->templateService->loadTemplate($templateName);

            // Fetch user and booking data
            $user = $this->fetchUserData($userId);
            $booking = $this->fetchBookingData($bookingId);

            // Render the template with dynamic data
            $data = [
                'user_name' => $user['name'],
                'user_email' => $user['email'],
                'vehicle' => $booking['vehicle_id'],
                'pickup_date' => $booking['pickup_date'],
                'dropoff_date' => $booking['dropoff_date'],
                'total_price' => $booking['total_price'],
            ];
            $renderedContent = $this->templateService->renderTemplate($templateName, $data);

            // Encrypt and store the contract
            $encryptedContract = $this->encryptionService->encrypt($renderedContent);
            $filePath = $this->fileStorage->storeFile("contracts", "contract_{$bookingId}.pdf", $encryptedContract);

            // Store contract metadata in the database
            $stmt = $this->db->prepare("
                INSERT INTO contracts (booking_id, user_id, contract_pdf, created_at) 
                VALUES (:booking_id, :user_id, :contract_pdf, NOW())
            ");
            $stmt->execute([
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'contract_pdf' => $filePath,
            ]);

            $this->auditService->log('contract_generated', ['booking_id' => $bookingId, 'user_id' => $userId]);

            return $filePath;
        } catch (Exception $e) {
            $this->logger->error('Failed to generate contract', ['error' => $e->getMessage()]);
            throw new Exception("Failed to generate contract: " . $e->getMessage());
        }
    }

    /**
     * Retrieve and decrypt a document.
     */
    public function retrieveDocument(string $filePath): string
    {
        try {
            $this->logger->info('Retrieving document', ['filePath' => $filePath]);

            // Load the encrypted document
            $encryptedContent = $this->fileStorage->loadFile($filePath);

            // Decrypt the content
            $decryptedContent = $this->encryptionService->decrypt($encryptedContent);

            // Log the action
            $this->auditService->log('document_retrieved', ['file_path' => $filePath]);

            return $decryptedContent;
        } catch (Exception $e) {
            $this->logger->error('Failed to retrieve document', ['error' => $e->getMessage()]);
            throw new Exception("Failed to retrieve document: " . $e->getMessage());
        }
    }

    /**
     * Upload the Terms & Conditions document.
     */
    public function uploadTerms(string $content): string
    {
        try {
            $this->logger->info('Uploading Terms & Conditions document');

            // Encrypt and store the T&C content
            $encryptedContent = $this->encryptionService->encrypt($content);
            $filePath = $this->fileStorage->storeFile('templates', 'terms_and_conditions.html', $encryptedContent);

            // Log the upload
            $this->auditService->log('terms_uploaded', ['file_path' => $filePath]);

            return $filePath;
        } catch (Exception $e) {
            $this->logger->error('Failed to upload Terms & Conditions', ['error' => $e->getMessage()]);
            throw new Exception("Failed to upload Terms and Conditions: " . $e->getMessage());
        }
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(int $documentId): void
    {
        try {
            $this->logger->info('Deleting document', ['documentId' => $documentId]);

            // Fetch the document metadata
            $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = :document_id");
            $stmt->execute(['document_id' => $documentId]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                throw new Exception("Document not found.");
            }

            // Delete the file and remove the database entry
            $this->fileStorage->deleteFile($document['file_path']);
            $stmt = $this->db->prepare("DELETE FROM documents WHERE id = :document_id");
            $stmt->execute(['document_id' => $documentId]);

            $this->auditService->log('document_deleted', ['document_id' => $documentId]);
        } catch (Exception $e) {
            $this->logger->error('Failed to delete document', ['error' => $e->getMessage()]);
            throw new Exception("Failed to delete document: " . $e->getMessage());
        }
    }

    /**
     * Fetch user data.
     */
    private function fetchUserData(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User not found.");
        }

        return $user;
    }

    /**
     * Fetch booking data.
     */
    private function fetchBookingData(int $bookingId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE id = :booking_id");
        $stmt->execute(['booking_id' => $bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception("Booking not found.");
        }

        return $booking;
    }
}
