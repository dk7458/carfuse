<?php

namespace DocumentManager\Controllers;

use DocumentManager\Services\SignatureService;
use App\Services\Validator;
use Psr\Log\LoggerInterface;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

/**
 * Signature Controller
 *
 * Handles the management of user signatures, including uploading,
 * verifying, and retrieving signatures for documents.
 */
class SignatureController
{
    private SignatureService $signatureService;
    private Validator $validator;
    private LoggerInterface $logger;

    public function __construct(
        SignatureService $signatureService,
        Validator $validator,
        LoggerInterface $logger
    ) {
        $this->signatureService = $signatureService;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * Upload a signature.
     *
     * @param array $data The uploaded signature file and associated metadata.
     * @return array Response indicating success or failure.
     */
    public function uploadSignature(array $data): array
    {
        $rules = [
            'user_id' => 'required|integer',
            'file' => 'required|file|mimes:png,jpg,jpeg|max:2048', // Max 2MB
        ];

        if (!$this->validator->validate($data, $rules)) {
            $this->logger->warning('Signature validation failed', ['data' => $data]);
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $signaturePath = $this->signatureService->uploadSignature($data['user_id'], $data['file']);
            $this->logger->info('Signature uploaded successfully', ['user_id' => $data['user_id']]);

            return ['status' => 'success', 'message' => 'Signature uploaded successfully', 'signature_path' => $signaturePath];
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload signature', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to upload signature'];
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
            $isValid = $this->signatureService->verifySignature($userId, $documentHash);

            if ($isValid) {
                $this->logger->info('Signature verified successfully', ['user_id' => $userId]);
                return ['status' => 'success', 'message' => 'Signature verified successfully'];
            }

            return ['status' => 'error', 'message' => 'Signature verification failed'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to verify signature', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to verify signature'];
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
            $signaturePath = $this->signatureService->getSignature($userId);

            if ($signaturePath) {
                $this->logger->info('Signature retrieved successfully', ['user_id' => $userId]);
                return ['status' => 'success', 'signature_path' => $signaturePath];
            }

            return ['status' => 'error', 'message' => 'Signature not found'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve signature', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to retrieve signature'];
        }
    }
}
