<?php

namespace App\Controllers;

use App\Services\DocumentService;
use App\Services\EncryptionService;
use App\Services\FileStorage;
use App\Services\Validator;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use App\Models\DocumentTemplate;
use App\Models\AuditLog;
use App\Services\TokenService;

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
            return $this->jsonResponse(['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()]);
        }

        try {
            // Store file using FileStorage service
            $filePath = FileStorage::store($data['file']);
            // Create a new template using Eloquent ORM
            $template = DocumentTemplate::create([
                'name' => $data['name'],
                'file_path' => $filePath,
            ]);
            // Log document creation using AuditLog model
            AuditLog::create([
                'action' => 'template_uploaded',
                'message' => 'Template uploaded successfully.',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            return $this->jsonResponse(['status' => 'success', 'message' => 'Template uploaded successfully', 'template_id' => $template->id]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload template: ' . $e->getMessage());
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to upload template']);
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
            // Log the contract generation using AuditLog model
            AuditLog::create([
                'action' => 'contract_generated',
                'message' => 'Contract generated successfully.',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            return $this->jsonResponse(['status' => 'success', 'message' => 'Contract generated successfully', 'contract_path' => $contractPath]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate contract: ' . $e->getMessage());
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to generate contract']);
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
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $path = $this->documentService->uploadTerms($data['file']);
            $this->auditService->log(
                'terms_uploaded',
                'Terms and Conditions document uploaded.',
                null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            return $this->jsonResponse(['status' => 'success', 'message' => 'T&C document uploaded successfully']);
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload T&C document: ' . $e->getMessage());
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to upload T&C document']);
        }
    }

    /**
     * Generate an invoice for a booking.
     */
    public function generateInvoice(int $bookingId): array
    {
        try {
            $invoicePath = $this->documentService->generateInvoice($bookingId);
            $this->auditService->log(
                'invoice_generated',
                'Invoice generated successfully.',
                null,
                $bookingId,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            return ['status' => 'success', 'message' => 'Invoice generated successfully', 'invoice_path' => $invoicePath];
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate invoice: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to generate invoice'];
        }
    }

    /**
     * Delete a document (template or user-specific).
     */
    public function deleteDocument(int $documentId): array
    {
        try {
            $this->documentService->deleteDocument($documentId);
            $this->auditService->log(
                'document_deleted',
                'Document deleted successfully.',
                null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            return ['status' => 'success', 'message' => 'Document deleted successfully'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete document: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to delete document'];
        }
    }

    public function uploadDocument()
    {
        $user = TokenService::getUserFromToken(request()->bearerToken());

        if (!$user) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        // ...existing code...

        return $this->jsonResponse(['success' => true, 'message' => 'Document uploaded successfully']);
    }
}
