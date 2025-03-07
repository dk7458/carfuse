<?php

namespace App\Controllers;

use App\Services\DocumentService;
use App\Services\Validator;
use App\Services\AuditService;
use App\Helpers\ExceptionHandler;
use Psr\Log\LoggerInterface;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class DocumentController extends Controller
{
    private DocumentService $documentService;
    private Validator $validator;
    private AuditService $auditService;
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        DocumentService $documentService,
        Validator $validator,
        AuditService $auditService,
        ExceptionHandler $exceptionHandler
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->documentService = $documentService;
        $this->validator = $validator;
        $this->auditService = $auditService;
        $this->exceptionHandler = $exceptionHandler;
    }
    
    /**
     * Upload a document template.
     */
    public function uploadTemplate(array $data): array
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'content' => 'required|string',
            ];

            if (!$this->validator->validate($data, $rules)) {
                return $this->jsonResponse('error', ['message' => 'Validation failed', 'errors' => $this->validator->errors()], 400);
            }

            // Delegate template upload to service
            $this->documentService->uploadTemplate($data['name'], $data['content']);
            
            return $this->jsonResponse('success', ['message' => 'Template uploaded successfully'], 201);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', ['message' => 'Failed to upload template'], 500);
        }
    }

    /**
     * Generate a contract for a booking.
     */
    public function generateContract(int $bookingId, int $userId): array
    {
        try {
            // Delegate contract generation to service
            $contractPath = $this->documentService->generateContractSecure($bookingId, $userId);
            
            return $this->jsonResponse('success', ['message' => 'Contract generated successfully', 'contract_path' => $contractPath], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', ['message' => 'Failed to generate contract'], 500);
        }
    }

    /**
     * Upload and manage the Terms & Conditions document.
     */
    public function uploadTerms(array $data): array
    {
        try {
            $rules = [
                'content' => 'required|string',
            ];

            if (!$this->validator->validate($data, $rules)) {
                return $this->jsonResponse('error', ['message' => 'Validation failed', 'errors' => $this->validator->errors()], 400);
            }

            // Delegate T&C upload to service
            $this->documentService->uploadTerms($data['content']);

            return $this->jsonResponse('success', ['message' => 'T&C document uploaded successfully'], 201);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', ['message' => 'Failed to upload T&C document'], 500);
        }
    }

    /**
     * Generate an invoice for a booking.
     */
    public function generateInvoice(int $bookingId): array
    {
        try {
            // Delegate invoice generation to service
            $invoicePath = $this->documentService->generateInvoice($bookingId);

            return $this->jsonResponse('success', ['message' => 'Invoice generated successfully', 'invoice_path' => $invoicePath], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', ['message' => 'Failed to generate invoice'], 500);
        }
    }

    /**
     * Delete a document (template or user-specific).
     */
    public function deleteDocument(int $documentId): array
    {
        try {
            // Delegate document deletion to service
            $this->documentService->deleteDocument($documentId);

            return $this->jsonResponse('success', ['message' => 'Document deleted successfully'], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', ['message' => 'Failed to delete document'], 500);
        }
    }
    
    /**
     * Get all templates
     */
    public function getTemplates(): array
    {
        try {
            // Delegate to service
            $templates = $this->documentService->getTemplates();
            
            return $this->jsonResponse('success', ['templates' => $templates], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', ['message' => 'Failed to retrieve templates'], 500);
        }
    }
    
    /**
     * Get a specific template
     */
    public function getTemplate(int $templateId): array
    {
        try {
            // Delegate to service
            $template = $this->documentService->getTemplateById($templateId);
            
            return $this->jsonResponse('success', ['template' => $template], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', ['message' => 'Failed to retrieve template'], 500);
        }
    }
}
