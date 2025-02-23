<?php

namespace App\Controllers;

use App\Services\SignatureService;
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
    private LoggerInterface $logger;

    public function __construct(
        SignatureService $signatureService,
        LoggerInterface $signatureLogger
    ) {
        $this->signatureService = $signatureService;
        $this->logger = $signatureLogger;
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

        try {
            custom_validate($data, $rules);
        } catch (\Exception $ex) {
            $this->logger->error("Warning: Signature validation failed. Data: " . json_encode($data));
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $ex->getMessage()];
        }

        try {
            $signaturePath = $this->signatureService->uploadSignature($data['user_id'], $data['file']);
            $this->logger->info("Info: Signature uploaded successfully for user_id: " . $data['user_id']);
            return ['status' => 'success', 'message' => 'Signature uploaded successfully', 'signature_path' => $signaturePath];
        } catch (\Exception $e) {
            $this->logger->error("Error: Failed to upload signature, error: " . $e->getMessage());
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
                $this->logger->info("Info: Signature verified successfully for user_id: {$userId}");
                return ['status' => 'success', 'message' => 'Signature verified successfully'];
            }

            return ['status' => 'error', 'message' => 'Signature verification failed'];
        } catch (\Exception $e) {
            $this->logger->error("Error: Failed to verify signature, error: " . $e->getMessage());
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
                $this->logger->info("Info: Signature retrieved successfully for user_id: {$userId}");
                return ['status' => 'success', 'signature_path' => $signaturePath];
            }

            return ['status' => 'error', 'message' => 'Signature not found'];
        } catch (\Exception $e) {
            $this->logger->error("Error: Failed to retrieve signature, error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to retrieve signature'];
        }
    }
}
