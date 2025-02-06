<?php

namespace DocumentManager\Controllers;

use DocumentManager\Services\DocumentService;
use App\Services\EncryptionService;
use DocumentManager\Services\FileStorage;
use App\Services\Validator;
use AuditManager\Services\AuditService;
use Psr\Log\LoggerInterface;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class DocumentController
{
    private DocumentService $documentService;
    private Validator $validator;
    private AuditService $auditService;
    private LoggerInterface $logger;

    public function __construct(
        DocumentService $documentService,
        Validator $validator,
        AuditService $auditService,
        LoggerInterface $logger
    ) {
        $this->documentService = $documentService;
        $this->validator = $validator;
        $this->auditService = $auditService;
        $this->logger = $logger;
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
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $templateId = $this->documentService->uploadTemplate($data['name'], $data['file']);
            $this->auditService->log(
                'template_uploaded',
                'Template uploaded successfully.',
                null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            return ['status' => 'success', 'message' => 'Template uploaded successfully', 'template_id' => $templateId];
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload template', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to upload template'];
        }
    }

    /**
     * Generate a contract for a booking.
     */
    public function generateContract(int $bookingId, int $userId): array
    {
        try {
            $contractPath = $this->documentService->generateContract($bookingId, $userId);
            $this->auditService->log(
                'contract_generated',
                'Contract generated successfully.',
                $userId,
                $bookingId,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            return ['status' => 'success', 'message' => 'Contract generated successfully', 'contract_path' => $contractPath];
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate contract', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to generate contract'];
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

            return ['status' => 'success', 'message' => 'T&C document uploaded successfully'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload T&C document', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to upload T&C document'];
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
            $this->logger->error('Failed to generate invoice', ['error' => $e->getMessage()]);
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
            $this->logger->error('Failed to delete document', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to delete document'];
        }
    }
}
