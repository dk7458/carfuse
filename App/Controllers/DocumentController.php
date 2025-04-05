<?php

namespace App\Controllers;

use App\Services\DocumentService;
use App\Services\Validator;
use App\Services\AuditService;
use App\Services\RateLimitService;
use App\Helpers\ExceptionHandler;
use App\Helpers\ResponseHelper;
use Psr\Log\LoggerInterface;

require_once 'ViewHelper.php';

class DocumentController extends Controller
{
    private DocumentService $documentService;
    private Validator $validator;
    private AuditService $auditService;
    private RateLimitService $rateLimitService;
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        DocumentService $documentService,
        Validator $validator,
        AuditService $auditService,
        ExceptionHandler $exceptionHandler,
        RateLimitService $rateLimitService
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->documentService = $documentService;
        $this->validator = $validator;
        $this->auditService = $auditService;
        $this->exceptionHandler = $exceptionHandler;
        $this->rateLimitService = $rateLimitService;
    }
    
    /**
     * Apply rate limiting to the endpoint
     */
    private function applyRateLimit(string $endpoint, string $userTier = 'standard'): bool
    {
        $limits = [
            'standard' => [
                'default' => 20, // per minute
                'template' => 100, // per hour
                'generate' => 30, // per hour
            ],
            'premium' => [
                'default' => 50, // per minute
                'template' => 100, // per hour
                'generate' => 30, // per hour
            ]
        ];
        
        $limit = match($endpoint) {
            'template_operations' => $limits[$userTier]['template'],
            'document_generation' => $limits[$userTier]['generate'],
            default => $limits[$userTier]['default']
        };
        
        $timeUnit = match($endpoint) {
            'template_operations', 'document_generation' => 'hour',
            default => 'minute'
        };
        
        $limitInfo = $this->rateLimitService->check(
            $endpoint, 
            $this->getCurrentUserId(),
            $limit,
            $timeUnit
        );
        
        // Add rate limit headers
        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . $limitInfo['remaining']);
        header('X-RateLimit-Reset: ' . $limitInfo['reset']);
        
        return $limitInfo['allowed'];
    }
    
    /**
     * Upload a document template.
     */
    public function uploadTemplate(array $data): array
    {
        // Check rate limits
        if (!$this->applyRateLimit('template_operations', $this->getUserTier())) {
            return $this->jsonResponse('error', [
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Rate limit exceeded for template operations'
            ], 429);
        }

        try {
            $rules = [
                'name' => 'required|string|max:255',
                'content' => 'required|string',
                'description' => 'string|max:1000',
                'content_type' => 'required|in:html,markdown,pdf',
                'version' => 'required|string'
            ];

            if (!$this->validator->validate($data, $rules)) {
                return $this->jsonResponse('error', [
                    'error_code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed', 
                    'errors' => $this->validator->errors()
                ], 400);
            }

            // Check for duplicate template name
            if ($this->documentService->templateExists($data['name'])) {
                return $this->jsonResponse('error', [
                    'error_code' => 'TEMPLATE_EXISTS',
                    'message' => 'Template with this name already exists'
                ], 409);
            }
            
            // Delegate template upload to service
            $templateResult = $this->documentService->uploadTemplate(
                $data['name'], 
                $data['content'], 
                $data['content_type'] ?? 'html',
                $data['version'] ?? '1.0',
                $data['description'] ?? null
            );
            
            // Log activity for auditing
            $this->auditService->log(
                'template_created',
                'Template created: ' . $data['name'],
                $this->getCurrentUserId()
            );
            
            return $this->jsonResponse('success', [
                'message' => 'Template uploaded successfully',
                'data' => [
                    'template_id' => $templateResult['id'],
                    'name' => $templateResult['name'],
                    'created_at' => $templateResult['created_at']
                ]
            ], 201);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', [
                'error_code' => 'UPLOAD_FAILED',
                'message' => 'Failed to upload template'
            ], 500);
        }
    }

    /**
     * Generate a contract for a booking.
     */
    public function generateContract(int $bookingId, int $userId, array $params = []): array
    {
        // Check rate limits
        if (!$this->applyRateLimit('document_generation', $this->getUserTier())) {
            return $this->jsonResponse('error', [
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Rate limit exceeded for document generation'
            ], 429);
        }

        try {
            // Validate parameters
            $format = $params['format'] ?? 'pdf';
            if (!in_array($format, ['pdf', 'html'])) {
                return $this->jsonResponse('error', [
                    'error_code' => 'INVALID_FORMAT',
                    'message' => 'Invalid format specified'
                ], 400);
            }
            
            // Validate booking ownership
            if (!$this->documentService->validateBookingAccess($bookingId, $userId)) {
                return $this->jsonResponse('error', [
                    'error_code' => 'ACCESS_DENIED',
                    'message' => 'User does not have access to this booking'
                ], 403);
            }
            
            // Check if booking exists
            if (!$this->documentService->bookingExists($bookingId)) {
                return $this->jsonResponse('error', [
                    'error_code' => 'BOOKING_NOT_FOUND',
                    'message' => 'Booking not found'
                ], 404);
            }
            
            // Check if user exists
            if (!$this->documentService->userExists($userId)) {
                return $this->jsonResponse('error', [
                    'error_code' => 'USER_NOT_FOUND',
                    'message' => 'User not found'
                ], 404);
            }
            
            // Delegate contract generation to service
            $signatureRequired = $params['signature_required'] ?? true;
            $result = $this->documentService->generateContractSecure(
                $bookingId, 
                $userId, 
                $format,
                $signatureRequired
            );
            
            // Log activity for auditing
            $this->auditService->log(
                'contract_generated',
                'Contract generated for booking: ' . $bookingId,
                $this->getCurrentUserId()
            );

            // If HTML format is requested, return the HTML content
            if ($format === 'html') {
                return $this->jsonResponse('success', [
                    'message' => 'Contract generated successfully',
                    'data' => [
                        'contract_id' => $result['contract_id'],
                        'html' => $result['content'],
                        'created_at' => $result['created_at']
                    ]
                ], 200);
            }

            // Otherwise return metadata for PDF format
            return $this->jsonResponse('success', [
                'message' => 'Contract generated successfully',
                'data' => [
                    'contract_path' => $result['path'],
                    'contract_id' => $result['contract_id'],
                    'created_at' => $result['created_at']
                ]
            ], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', [
                'error_code' => 'GENERATION_FAILED',
                'message' => 'Failed to generate contract'
            ], 500);
        }
    }

    /**
     * Upload and manage the Terms & Conditions document.
     */
    public function uploadTerms(array $data): array
    {
        // Check rate limits
        if (!$this->applyRateLimit('template_operations', $this->getUserTier())) {
            return $this->jsonResponse('error', [
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Rate limit exceeded for template operations'
            ], 429);
        }

        try {
            $rules = [
                'content' => 'required|string',
                'version' => 'required|string',
                'effective_date' => 'required|date_format:Y-m-d\TH:i:s\Z'
            ];

            if (!$this->validator->validate($data, $rules)) {
                return $this->jsonResponse('error', [
                    'error_code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed', 
                    'errors' => $this->validator->errors()
                ], 400);
            }
            
            // Check if the version already exists
            if ($this->documentService->termsVersionExists($data['version'])) {
                return $this->jsonResponse('error', [
                    'error_code' => 'VERSION_EXISTS',
                    'message' => 'This version of terms already exists'
                ], 409);
            }

            // Delegate T&C upload to service
            $termsResult = $this->documentService->uploadTerms(
                $data['content'], 
                $data['version'], 
                $data['effective_date']
            );
            
            // Log activity for auditing
            $this->auditService->log(
                'terms_updated',
                'Terms & Conditions updated to version: ' . $data['version'],
                $this->getCurrentUserId()
            );

            return $this->jsonResponse('success', [
                'message' => 'T&C document uploaded successfully',
                'data' => [
                    'terms_id' => $termsResult['id'],
                    'version' => $termsResult['version'],
                    'effective_date' => $termsResult['effective_date'],
                    'created_at' => $termsResult['created_at']
                ]
            ], 201);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', [
                'error_code' => 'UPLOAD_FAILED',
                'message' => 'Failed to upload T&C document'
            ], 500);
        }
    }

    /**
     * Generate an invoice for a booking.
     */
    public function generateInvoice(int $bookingId, array $params = []): array
    {
        // Check rate limits
        if (!$this->applyRateLimit('document_generation', $this->getUserTier())) {
            return $this->jsonResponse('error', [
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Rate limit exceeded for document generation'
            ], 429);
        }

        try {
            // Validate format parameter
            $format = $params['format'] ?? 'pdf';
            if (!in_array($format, ['pdf', 'html'])) {
                return $this->jsonResponse('error', [
                    'error_code' => 'INVALID_FORMAT',
                    'message' => 'Invalid format specified'
                ], 400);
            }
            
            // Check if booking exists
            if (!$this->documentService->bookingExists($bookingId)) {
                return $this->jsonResponse('error', [
                    'error_code' => 'BOOKING_NOT_FOUND',
                    'message' => 'Booking not found'
                ], 404);
            }
            
            // Validate booking ownership
            if (!$this->documentService->validateBookingAccess($bookingId, $this->getCurrentUserId())) {
                return $this->jsonResponse('error', [
                    'error_code' => 'ACCESS_DENIED',
                    'message' => 'User does not have access to this booking'
                ], 403);
            }
            
            // Check if payment is complete
            if (!$this->documentService->hasCompletedPayment($bookingId)) {
                return $this->jsonResponse('error', [
                    'error_code' => 'PAYMENT_REQUIRED',
                    'message' => 'Booking has no associated payment'
                ], 404);
            }

            // Delegate invoice generation to service with tax calculation
            $result = $this->documentService->generateInvoice(
                $bookingId,
                $format,
                $params['include_tax'] ?? true
            );
            
            // Log activity for auditing
            $this->auditService->log(
                'invoice_generated',
                'Invoice generated for booking: ' . $bookingId,
                $this->getCurrentUserId()
            );

            // If HTML format is requested, return the HTML content
            if ($format === 'html') {
                return $this->jsonResponse('success', [
                    'message' => 'Invoice generated successfully',
                    'data' => [
                        'html' => $result['content'],
                        'invoice_id' => $result['invoice_id'],
                        'created_at' => $result['created_at'],
                        'payment_status' => $result['payment_status']
                    ]
                ], 200);
            }
            
            // For PDF format, return binary data with proper headers
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="invoice_booking_' . $bookingId . '.pdf"');
            echo $result['content'];
            exit;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', [
                'error_code' => 'GENERATION_FAILED',
                'message' => 'Failed to generate invoice'
            ], 500);
        }
    }

    /**
     * Delete a document (template or user-specific).
     */
    public function deleteDocument(int $documentId): array
    {
        try {
            // Validate document ID
            if ($documentId <= 0) {
                return $this->jsonResponse('error', [
                    'error_code' => 'INVALID_DOCUMENT_ID',
                    'message' => 'Invalid document ID'
                ], 400);
            }
            
            // Check if document exists
            if (!$this->documentService->documentExists($documentId)) {
                return $this->jsonResponse('error', [
                    'error_code' => 'DOCUMENT_NOT_FOUND',
                    'message' => 'Document not found'
                ], 404);
            }
            
            // Check if document can be deleted (not a system template or in use)
            if (!$this->documentService->canDeleteDocument($documentId)) {
                return $this->jsonResponse('error', [
                    'error_code' => 'DOCUMENT_IN_USE',
                    'message' => 'Document is in use and cannot be deleted'
                ], 409);
            }

            // Delegate document deletion to service (soft delete)
            $this->documentService->deleteDocument($documentId, true); // true = soft delete
            
            // Log activity for auditing
            $this->auditService->log(
                'document_deleted',
                'Document deleted: ' . $documentId,
                $this->getCurrentUserId()
            );

            // No content response for successful deletion
            return $this->jsonResponse('success', [], 204);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', [
                'error_code' => 'DELETE_FAILED',
                'message' => 'Failed to delete document'
            ], 500);
        }
    }
    
    /**
     * Get all templates
     */
    public function getTemplates(array $params = []): array
    {
        try {
            // Support pagination
            $page = $params['page'] ?? 1;
            $perPage = min(100, $params['per_page'] ?? 20);
            
            // Support filtering by type
            $type = $params['type'] ?? null;
            
            // Delegate to service
            $result = $this->documentService->getTemplates($page, $perPage, $type);
            
            return $this->jsonResponse('success', [
                'templates' => $result['templates'],
                'meta' => [
                    'current_page' => $result['current_page'],
                    'total_pages' => $result['total_pages'],
                    'total_items' => $result['total_items'],
                    'per_page' => $result['per_page']
                ]
            ], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', [
                'error_code' => 'SERVER_ERROR',
                'message' => 'Failed to retrieve templates'
            ], 500);
        }
    }
    
    /**
     * Get a specific template
     */
    public function getTemplate(int $templateId): array
    {
        try {
            // Validate template ID
            if ($templateId <= 0) {
                return $this->jsonResponse('error', [
                    'error_code' => 'INVALID_TEMPLATE_ID',
                    'message' => 'Invalid template ID'
                ], 400);
            }
            
            // Delegate to service
            $template = $this->documentService->getTemplateById($templateId);
            
            if (!$template) {
                return $this->jsonResponse('error', [
                    'error_code' => 'TEMPLATE_NOT_FOUND',
                    'message' => 'Template not found'
                ], 404);
            }
            
            // Extract and add template variables
            $variables = $this->documentService->extractTemplateVariables($template['content']);
            $template['variables'] = $variables;
            
            return $this->jsonResponse('success', [
                'template' => $template
            ], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', [
                'error_code' => 'SERVER_ERROR',
                'message' => 'Failed to retrieve template'
            ], 500);
        }
    }
    
    /**
     * Get a specific document by ID
     */
    public function getDocument(int $documentId): array
    {
        try {
            // Validate document ID
            if ($documentId <= 0) {
                return $this->jsonResponse('error', [
                    'error_code' => 'INVALID_DOCUMENT_ID',
                    'message' => 'Invalid document ID'
                ], 400);
            }
            
            // Check if document exists
            $document = $this->documentService->getDocumentById($documentId);
            
            if (!$document) {
                return $this->jsonResponse('error', [
                    'error_code' => 'DOCUMENT_NOT_FOUND',
                    'message' => 'Document not found'
                ], 404);
            }
            
            // Validate access permissions
            if (!$this->documentService->canAccessDocument($documentId, $this->getCurrentUserId())) {
                return $this->jsonResponse('error', [
                    'error_code' => 'ACCESS_DENIED',
                    'message' => 'Access denied to this document'
                ], 403);
            }
            
            return $this->jsonResponse('success', [
                'document' => $document
            ], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', [
                'error_code' => 'SERVER_ERROR',
                'message' => 'Failed to retrieve document'
            ], 500);
        }
    }
    
    /**
     * Search for documents with filtering
     */
    public function searchDocuments(array $params): array
    {
        try {
            // Support pagination
            $page = $params['page'] ?? 1;
            $perPage = min(100, $params['per_page'] ?? 20);
            
            // Support filtering
            $filters = [
                'type' => $params['type'] ?? null,
                'user_id' => $params['user_id'] ?? null,
                'booking_id' => $params['booking_id'] ?? null,
                'created_after' => $params['created_after'] ?? null,
                'created_before' => $params['created_before'] ?? null
            ];
            
            // Delegate to service
            $result = $this->documentService->searchDocuments($filters, $page, $perPage);
            
            return $this->jsonResponse('success', [
                'documents' => $result['documents'],
                'meta' => [
                    'current_page' => $result['current_page'],
                    'total_pages' => $result['total_pages'],
                    'total_items' => $result['total_items'],
                    'per_page' => $result['per_page']
                ]
            ], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', [
                'error_code' => 'SERVER_ERROR',
                'message' => 'Failed to search documents'
            ], 500);
        }
    }
    
    /**
     * Get the current user's ID
     */
    private function getCurrentUserId(): int
    {
        // Implementation depends on authentication system
        return $_SESSION['user_id'] ?? 0;
    }
    
    /**
     * Get the current user's subscription tier
     */
    private function getUserTier(): string
    {
        // Implementation depends on user management system
        return $_SESSION['user_tier'] ?? 'standard';
    }
}
