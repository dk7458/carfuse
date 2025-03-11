<?php

namespace App\Services;

use Exception;
use App\Helpers\DatabaseHelper;
use AuditManager\Services\AuditService;
use DocumentManager\Services\FileStorage;
use DocumentManager\Services\TemplateService;
use App\Services\EncryptionService;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\Contract;
use App\Models\User;
use App\Models\Booking;

/**
 * Document Service
 *
 * Manages documents including templates, contracts, and Terms & Conditions (T&C).
 * Supports encryption, secure storage, logging, and dynamic document generation.
 */
class DocumentService
{
    public const DEBUG_MODE = true;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private AuditService $auditService;
    private FileStorage $fileStorage;
    private EncryptionService $encryptionService;
    private TemplateService $templateService;
    private Document $documentModel;
    private DocumentTemplate $templateModel;
    private Contract $contractModel;
    private User $userModel;
    private Booking $bookingModel;
    private array $config;

    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService,
        FileStorage $fileStorage,
        EncryptionService $encryptionService,
        TemplateService $templateService,
        Document $documentModel,
        DocumentTemplate $templateModel,
        Contract $contractModel,
        User $userModel,
        Booking $bookingModel,
        array $config
    ) {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
        $this->fileStorage = $fileStorage;
        $this->encryptionService = $encryptionService;
        $this->templateService = $templateService;
        $this->documentModel = $documentModel;
        $this->templateModel = $templateModel;
        $this->contractModel = $contractModel;
        $this->userModel = $userModel;
        $this->bookingModel = $bookingModel;
        $this->config = $config;
    }

    /**
     * Upload a document template.
     */
    public function uploadTemplate(string $name, string $content): void
    {
        try {
            if (self::DEBUG_MODE) {
                $this->logger->info("[Document] Uploading template: {$name}");
            }
            
            $encryptedContent = $this->encryptionService->encrypt($content);
            $filePath = $this->fileStorage->storeFile("templates", "{$name}.html", $encryptedContent);
            
            // Use template model to find by name
            $existingTemplate = $this->templateModel->findByName($name);
            
            if ($existingTemplate) {
                // Update existing template using model
                $this->templateModel->update($existingTemplate['id'], [
                    'content' => $encryptedContent,
                    'file_path' => $filePath,
                ]);
            } else {
                // Create new template using model
                $this->templateModel->create([
                    'name' => $name,
                    'content' => $encryptedContent,
                    'file_path' => $filePath,
                ]);
            }
            
            // Business-level audit logging
            $this->auditService->log('template_uploaded', ['template' => $name]);
            
        } catch (Exception $e) {
            $this->logger->error("[Document] ❌ Upload template exception: " . $e->getMessage());
            throw new Exception("Failed to upload template: {$name} " . $e->getMessage());
        }
    }

    /**
     * Upload the Terms & Conditions document.
     */
    public function uploadTerms(string $content): void
    {
        $this->uploadTemplate('terms_and_conditions', $content);
    }

    /**
     * Generate a rental contract document dynamically.
     */
    public function generateContract(int $bookingId, int $userId): string
    {
        try {
            if (self::DEBUG_MODE) {
                $this->logger->info("[Document] Generating contract for booking {$bookingId}");
            }

            // Use models to get data
            $templateData = $this->templateModel->findByName('rental_contract');
            if (!$templateData) {
                throw new Exception("Contract template not found");
            }
            
            $userData = $this->userModel->find($userId);
            $bookingData = $this->bookingModel->find($bookingId);
            
            if (!$userData || !$bookingData) {
                throw new Exception("User or booking data not found");
            }

            // Prepare data for template rendering
            $data = array_merge($userData, $bookingData);
            
            // Decrypt template content and render with data
            $templateContent = $this->encryptionService->decrypt($templateData['content']);
            $renderedContent = $this->templateService->renderTemplateContent($templateContent, $data);

            // Encrypt the rendered content
            $encryptedContract = $this->encryptionService->encrypt($renderedContent);
            
            // Generate filename using config pattern
            $timestamp = time();
            $filename = str_replace(
                ['{booking_id}', '{timestamp}'], 
                [$bookingId, $timestamp], 
                $this->config['naming']['contract_format']
            );
            
            // Store the file
            $filePath = $this->fileStorage->storeFile(
                $this->config['storage']['contracts'], 
                $filename, 
                $encryptedContract
            );

            // Store contract record using contract model
            $this->contractModel->create([
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'contract_pdf' => $filePath,
            ]);

            // Business-level audit log for contract generation
            $this->auditService->log('contract_generated', [
                'booking_id' => $bookingId, 
                'user_id' => $userId
            ]);

            return $filePath;
        } catch (Exception $e) {
            $this->logger->error("[Document] ❌ Contract generation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate a contract securely (used by controller)
     */
    public function generateContractSecure(int $bookingId, int $userId): string
    {
        return $this->generateContract($bookingId, $userId);
    }

    /**
     * Retrieve and decrypt a document.
     */
    public function retrieveDocument(string $filePath): string
    {
        try {
            if (self::DEBUG_MODE) {
                $this->logger->info("[Document] Retrieving document from {$filePath}");
            }

            $encryptedContent = $this->fileStorage->retrieveFile($filePath);
            $decryptedContent = $this->encryptionService->decrypt($encryptedContent);

            // Business-level audit log for document retrieval
            $this->auditService->log('document_retrieved', ['file_path' => $filePath]);

            return $decryptedContent;
        } catch (Exception $e) {
            $this->logger->error("[Document] ❌ Retrieve document error: " . $e->getMessage());
            throw new Exception("Failed to retrieve document " . $e->getMessage());
        }
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(int $documentId): void
    {
        try {
            if (self::DEBUG_MODE) {
                $this->logger->info("[Document] Deleting document ID {$documentId}");
            }

            // Get document using model
            $document = $this->documentModel->find($documentId);

            if (!$document) {
                throw new Exception("Document not found.");
            }

            // Delete the physical file
            $this->fileStorage->deleteFile($document['file_path']);
            
            // Delete the document record using model
            $this->documentModel->delete($documentId);

            // Business-level audit log for document deletion
            $this->auditService->log('document_deleted', ['document_id' => $documentId]);
            
        } catch (Exception $e) {
            $this->logger->error("[Document] ❌ Delete document error: " . $e->getMessage());
            throw new Exception("Failed to delete document " . $e->getMessage());
        }
    }

    /**
     * Get a list of available templates.
     */
    public function getTemplates(): array
    {
        try {
            // Use template model to get all templates
            $templates = $this->templateModel->getAll();
            
            // Return only necessary information
            return array_map(function($template) {
                return [
                    'id' => $template['id'],
                    'name' => $template['name'],
                    'created_at' => $template['created_at'],
                    'updated_at' => $template['updated_at']
                ];
            }, $templates);
            
        } catch (Exception $e) {
            $this->logger->error("[Document] ❌ Get templates error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get a specific template by ID.
     */
    public function getTemplateById(int $templateId): array
    {
        try {
            // Use template model to get template by ID
            $template = $this->templateModel->find($templateId);
            
            if (!$template) {
                throw new Exception("Template not found.");
            }
            
            // Decrypt the content for use
            $template['content'] = $this->encryptionService->decrypt($template['content']);
            
            return $template;
        } catch (Exception $e) {
            $this->logger->error("[Document] ❌ Get template error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get contracts for a specific user.
     */
    public function getUserContracts(int $userId): array
    {
        try {
            // Use contract model to get user contracts
            return $this->contractModel->getByUserId($userId);
        } catch (Exception $e) {
            $this->logger->error("[Document] ❌ Get user contracts error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get contract for a specific booking.
     */
    public function getBookingContract(int $bookingId): array
    {
        try {
            // Use contract model to get booking contract
            $contract = $this->contractModel->getByBookingId($bookingId);
            
            if (!$contract) {
                throw new Exception("Contract not found for booking.");
            }
            
            return $contract;
        } catch (Exception $e) {
            $this->logger->error("[Document] ❌ Get booking contract error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate an invoice
     */
    public function generateInvoice(int $bookingId): string
    {
        try {
            $this->logger->info("[Document] Generating invoice for booking {$bookingId}");
            
            // Implementation similar to generateContract but for invoices
            // Using models instead of direct DB access
            
            $bookingData = $this->bookingModel->find($bookingId);
            if (!$bookingData) {
                throw new Exception("Booking not found");
            }
            
            $userId = $bookingData['user_id'];
            $userData = $this->userModel->find($userId);
            
            if (!$userData) {
                throw new Exception("User data not found");
            }
            
            // Further implementation...
            
            // For now, return a placeholder
            return "invoice_path_placeholder";
            
        } catch (Exception $e) {
            $this->logger->error("[Document] ❌ Invoice generation error: " . $e->getMessage());
            throw $e;
        }
    }
}
