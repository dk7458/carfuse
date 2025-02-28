<?php

namespace App\Controllers;

use App\Services\DocumentService;
use App\Services\EncryptionService;
use App\Services\FileStorage;
use App\Services\Validator;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use App\Models\DocumentTemplate;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class DocumentController extends Controller
{
    private DocumentService $documentService;
    private Validator $validator;
    private AuditService $auditService;
    private LoggerInterface $logger;

    public function __construct(
        DocumentService $documentService,
        Validator $validator,
        AuditService $auditService,
        LoggerInterface $documentLogger
    ) {
        $this->documentService = $documentService;
        $this->validator = $validator;
        $this->auditService = $auditService;
        $this->logger = $documentLogger;
    }
    
    /**
     * Upload a document template.
     */
    public function uploadTemplate(array $data): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,docx|max:10240', // Max 10MB
        ];

        if (!$this->validator->validate($data, $rules)) {
            return $this->jsonResponse('error', ['message' => 'Validation failed', 'errors' => $this->validator->errors()], 400);
        }

        try {
            // Store file using FileStorage service
            $filePath = FileStorage::store($data['file']);
            // Create a new template using Eloquent ORM
            $template = DocumentTemplate::create([
                'name' => $data['name'],
                'file_path' => $filePath,
            ]);
            
            // Log document creation using unified audit service
            $this->auditService->logEvent(
                'document', 
                'Template uploaded successfully', 
                ['template_id' => $template->id, 'template_name' => $data['name']],
                $_SESSION['user_id'] ?? null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return $this->jsonResponse('success', ['message' => 'Template uploaded successfully', 'template_id' => $template->id], 201);
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload template: ' . $e->getMessage());
            return $this->jsonResponse('error', ['message' => 'Failed to upload template'], 500);
        }
    }

    /**
     * Generate a contract for a booking.
     */
    public function generateContract(int $bookingId, int $userId): array
    {
        try {
            // Use a secure contract generation method ensuring encryption is applied
            $contractPath = $this->documentService->generateContractSecure($bookingId, $userId);
            
            // Log the contract generation using unified audit service
            $this->auditService->logEvent(
                'document',
                'Contract generated successfully',
                ['contract_type' => 'booking'], 
                $userId,
                $bookingId,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return $this->jsonResponse('success', ['message' => 'Contract generated successfully', 'contract_path' => $contractPath], 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate contract: ' . $e->getMessage());
            return $this->jsonResponse('error', ['message' => 'Failed to generate contract'], 500);
        }
    }

    /**
     * Upload and manage the Terms & Conditions document.
     */
    public function uploadTerms(array $data): array
    {
        $rules = [
            'file' => 'required|file|mimes:pdf|max:5120', // Max 5MB
        ];

        if (!$this->validator->validate($data, $rules)) {
            return $this->jsonResponse('error', ['message' => 'Validation failed', 'errors' => $this->validator->errors()], 400);
        }

        try {
            $path = $this->documentService->uploadTerms($data['file']);
            
            // Log using the unified audit service
            $this->auditService->logEvent(
                'document',
                'Terms and Conditions document uploaded',
                ['document_type' => 'terms_conditions', 'path' => $path],
                $_SESSION['user_id'] ?? null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            return $this->jsonResponse('success', ['message' => 'T&C document uploaded successfully'], 201);
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload T&C document: ' . $e->getMessage());
            return $this->jsonResponse('error', ['message' => 'Failed to upload T&C document'], 500);
        }
    }

    /**
     * Generate an invoice for a booking.
     */
    public function generateInvoice(int $bookingId): array
    {
        try {
            $invoicePath = $this->documentService->generateInvoice($bookingId);
            
            // Log using the unified audit service
            $this->auditService->logEvent(
                'document',
                'Invoice generated successfully',
                ['document_type' => 'invoice'],
                $_SESSION['user_id'] ?? null,
                $bookingId,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            return $this->jsonResponse('success', ['message' => 'Invoice generated successfully', 'invoice_path' => $invoicePath], 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate invoice: ' . $e->getMessage());
            return $this->jsonResponse('error', ['message' => 'Failed to generate invoice'], 500);
        }
    }

    /**
     * Delete a document (template or user-specific).
     */
    public function deleteDocument(int $documentId): array
    {
        try {
            $this->documentService->deleteDocument($documentId);
            
            // Log using the unified audit service
            $this->auditService->logEvent(
                'document',
                'Document deleted successfully',
                ['document_id' => $documentId],
                $_SESSION['user_id'] ?? null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            return $this->jsonResponse('success', ['message' => 'Document deleted successfully'], 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete document: ' . $e->getMessage());
            return $this->jsonResponse('error', ['message' => 'Failed to delete document'], 500);
        }
    }
}
