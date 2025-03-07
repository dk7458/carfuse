<?php

namespace App\Controllers;

use App\Services\SignatureService;
use App\Services\AuditService;
use App\Services\Auth\TokenService;
use App\Helpers\ExceptionHandler;
use Psr\Log\LoggerInterface;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

/**
 * Signature Controller
 *
 * Handles the management of user signatures, including uploading,
 * verifying, and retrieving signatures for documents.
 */
class SignatureController extends Controller
{
    private SignatureService $signatureService;
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;
    private AuditService $auditService;
    private TokenService $tokenService;

    public function __construct(
        LoggerInterface $logger,
        SignatureService $signatureService,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService,
        TokenService $tokenService
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->signatureService = $signatureService;
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
        $this->tokenService = $tokenService;
    }

    /**
     * Upload a signature.
     *
     * @param array $data The uploaded signature file and associated metadata.
     * @return array Response containing upload status
     */
    public function uploadSignature(array $data): array
    {
        try {
            $user = $this->tokenService->validateRequest($this->request->getHeader('Authorization'));
            if (!$user) {
                return ['status' => 'error', 'message' => 'Unauthorized access', 'code' => 401];
            }

            $rules = [
                'user_id' => 'required|integer',
                'file' => 'required|file|mimes:png,jpg,jpeg|max:2048', // Max 2MB
            ];

            $this->validator->validate($data, $rules);

            $signaturePath = $this->signatureService->uploadSignature($data['file'], $data['user_id']);
            
            // Log the signature upload event
            $this->auditService->logEvent(
                'signature_uploaded',
                "Signature uploaded successfully",
                ['user_id' => $data['user_id']],
                $user['id'],
                null,
                'document'
            );
            
            return ['status' => 'success', 'message' => 'Signature uploaded successfully', 'data' => $signaturePath];
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return ['status' => 'error', 'message' => 'Failed to upload signature', 'code' => 500];
        }
    }

    /**
     * Verify a signature.
     *
     * @param int $userId The ID of the user whose signature is to be verified.
     * @param string $documentHash The hash of the document to verify against the signature.
     * @return array Verification result.
     */
    public function verifySignature(int $userId, string $documentHash): array
    {
        try {
            $user = $this->tokenService->validateRequest($this->request->getHeader('Authorization'));
            if (!$user) {
                return ['status' => 'error', 'message' => 'Unauthorized access', 'code' => 401];
            }
            
            $isValid = $this->signatureService->verifySignature($userId, $documentHash);
            
            // Log the signature verification attempt
            $this->auditService->logEvent(
                'signature_verified',
                "Signature verification " . ($isValid ? "successful" : "failed"),
                [
                    'user_id' => $userId,
                    'document_hash' => substr($documentHash, 0, 10) . '...',
                    'result' => $isValid ? 'valid' : 'invalid'
                ],
                $user['id'],
                null,
                'document'
            );

            if ($isValid) {
                return ['status' => 'success', 'message' => 'Signature verified successfully'];
            }

            return ['status' => 'error', 'message' => 'Signature verification failed', 'code' => 400];
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return ['status' => 'error', 'message' => 'Failed to verify signature', 'code' => 500];
        }
    }

    /**
     * Retrieve a user's signature.
     *
     * @param int $userId The ID of the user.
     * @return array Response containing the signature path or error message.
     */
    public function getSignature(int $userId): array
    {
        try {
            $requestingUser = $this->tokenService->validateRequest($this->request->getHeader('Authorization'));
            if (!$requestingUser) {
                return ['status' => 'error', 'message' => 'Unauthorized access', 'code' => 401];
            }
            
            $signaturePath = $this->signatureService->getSignature($userId);
            
            // Log the signature retrieval
            $this->auditService->logEvent(
                'signature_retrieved',
                "Signature retrieved " . ($signaturePath ? "successfully" : "failed - not found"),
                [
                    'user_id' => $userId,
                    'requested_by' => $requestingUser['id']
                ],
                $requestingUser['id'],
                null,
                'document'
            );

            if ($signaturePath) {
                return ['status' => 'success', 'message' => 'Signature retrieved successfully', 'data' => $signaturePath];
            }

            return ['status' => 'error', 'message' => 'Signature not found', 'code' => 404];
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return ['status' => 'error', 'message' => 'Failed to retrieve signature', 'code' => 500];
        }
    }
}
