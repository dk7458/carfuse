<?php

namespace App\Controllers;

use App\Services\SignatureService;
// Removed: use App\Services\Validator;
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
    // private Validator $validator;
    // private LoggerInterface $logger;

    public function __construct(
        SignatureService $signatureService,
        /* Removed Validator */ $validator,
        /* Removed LoggerInterface */ $logger
    ) {
        $this->signatureService = $signatureService;
        // Assignments removed for validator and logger as we use custom validation and error_log()
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
            error_log("Warning: Signature validation failed. Data: " . json_encode($data));
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $ex->getMessage()];
        }

        try {
            $signaturePath = $this->signatureService->uploadSignature($data['user_id'], $data['file']);
            error_log("Info: Signature uploaded successfully for user_id: " . $data['user_id']);
            return ['status' => 'success', 'message' => 'Signature uploaded successfully', 'signature_path' => $signaturePath];
        } catch (\Exception $e) {
            error_log("Error: Failed to upload signature, error: " . $e->getMessage());
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
                error_log("Info: Signature verified successfully for user_id: {$userId}");
                return ['status' => 'success', 'message' => 'Signature verified successfully'];
            }

            return ['status' => 'error', 'message' => 'Signature verification failed'];
        } catch (\Exception $e) {
            error_log("Error: Failed to verify signature, error: " . $e->getMessage());
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
                error_log("Info: Signature retrieved successfully for user_id: {$userId}");
                return ['status' => 'success', 'signature_path' => $signaturePath];
            }

            return ['status' => 'error', 'message' => 'Signature not found'];
        } catch (\Exception $e) {
            error_log("Error: Failed to retrieve signature, error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to retrieve signature'];
        }
    }
}
