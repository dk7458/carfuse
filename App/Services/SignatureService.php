<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use App\Services\FileStorage;
use App\Services\EncryptionService;
use App\Helpers\DatabaseHelper; // new import

/**
 * Signature Service
 *
 * Manages electronic signatures both locally and via an external AES API.
 */
class SignatureService
{
    private string $apiEndpoint;
    private string $apiKey;
    private FileStorage $fileStorage;
    private EncryptionService $encryptionService;
    private LoggerInterface $logger;
    private $db; // DatabaseHelper instance

    public function __construct(
        array $config,
        FileStorage $fileStorage,
        EncryptionService $encryptionService,
        LoggerInterface $logger
    ) {
        if (empty($config['api_endpoint']) || empty($config['api_key'])) {
            throw new Exception('AES API configuration is incomplete.');
        }

        $this->apiEndpoint = $config['api_endpoint'];
        $this->apiKey = $config['api_key'];
        $this->fileStorage = $fileStorage;
        $this->encryptionService = $encryptionService;
        $this->logger = $logger;
        $this->db = DatabaseHelper::getInstance();
    }

    /**
     * Upload a local signature securely.
     */
    public function uploadSignature(string $filePath, int $userId): string
    {
        $this->validateFileType($filePath);

        $encryptedContent = $this->encryptionService->encrypt(file_get_contents($filePath));
        $fileName = uniqid() . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
        $storagePath = $this->fileStorage->storeFile("signatures/{$userId}", $fileName, $encryptedContent, false);

        // Replace direct Eloquent call with DatabaseHelper, wrapped in try–catch.
        try {
            $this->db->table('signatures')->insert([
                'user_id'   => $userId,
                'file_path' => $storagePath,
                'encrypted' => true,
                'created_at'=> date('Y-m-d H:i:s'),
            ]);
            $this->logger->info("[SignatureService] Signature record created for user {$userId}");
        } catch (Exception $e) {
            $this->logger->error("[SignatureService] Database error: " . $e->getMessage());
            throw $e;
        }
        $this->logger->info("[SignatureService] Signature uploaded for user {$userId} at {$storagePath}");
        return $storagePath;
    }

    /**
     * Send a document for AES signature.
     */
    public function sendForAdvancedSignature(string $filePath, int $userId, string $callbackUrl): array
    {
        try {
            $documentHash = hash_file('sha256', $filePath);

            $client = new Client();
            $response = $client->post("{$this->apiEndpoint}/sign-aes", [
                'headers' => $this->getAuthHeaders(),
                'multipart' => [
                    ['name' => 'file', 'contents' => fopen($filePath, 'r')],
                    ['name' => 'user_id', 'contents' => $userId],
                    ['name' => 'document_hash', 'contents' => $documentHash],
                    ['name' => 'callback_url', 'contents' => $callbackUrl],
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            $this->logAndThrow("Failed to send document for AES signing", $e);
        }
    }

    /**
     * Verify an AES signature using Laravel HTTP client.
     */
    public function verifySignature(string $signedFilePath, string $originalFilePath): bool
    {
        try {
            $originalHash = hash_file('sha256', $originalFilePath);
            $signedHash = hash_file('sha256', $signedFilePath);
            
            $client = new Client();
            $response = $client->post("{$this->apiEndpoint}/verify-aes", [
                'headers' => $this->getAuthHeaders(),
                'json' => [
                    'original_hash' => $originalHash,
                    'signed_hash'   => $signedHash,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info("Signature verification", ['result' => $result]);
            return $result['verified'] ?? false;
        } catch (Exception $e) {
            $this->logger->error("Failed to verify signature", ['error' => $e->getMessage()]);
            throw new Exception("Failed to verify signature: " . $e->getMessage());
        }
    }

    /**
     * Retrieve stored local signatures for a user.
     */
    public function getSignatures(int $userId): array
    {
        $storedSignatures = $this->fileStorage->retrieveFiles("signatures/{$userId}");

        if (empty($storedSignatures)) {
            throw new Exception('No signatures found.');
        }

        return array_map(fn($path) => $this->encryptionService->decrypt($this->fileStorage->retrieveFile($path, false)), $storedSignatures);
    }

    /**
     * Check the status of an AES signature request.
     */
    public function checkAdvancedSignatureStatus(string $requestId): array
    {
        try {
            $client = new Client();
            $response = $client->get("{$this->apiEndpoint}/status/{$requestId}", [
                'headers' => $this->getAuthHeaders(),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            $this->logAndThrow("Failed to check AES signature status", $e);
        }
    }

    /**
     * Download a signed AES document.
     */
    public function downloadSignedDocument(string $requestId, string $outputPath): bool
    {
        try {
            $client = new Client();
            $response = $client->get("{$this->apiEndpoint}/download/{$requestId}", [
                'headers' => $this->getAuthHeaders(),
                'sink' => $outputPath,
            ]);

            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            $this->logAndThrow("Failed to download AES signed document", $e);
        }
    }

    /**
     * Get authentication headers for API requests.
     */
    private function getAuthHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ];
    }

    /**
     * Validate allowed file types.
     */
    private function validateFileType(string $filePath): void
    {
        $allowedExtensions = ['png', 'jpg', 'svg'];
        if (!in_array(pathinfo($filePath, PATHINFO_EXTENSION), $allowedExtensions)) {
            throw new Exception('Invalid file type.');
        }
    }

    /**
     * Log error and throw exception.
     */
    private function logAndThrow(string $message, Exception $e): void
    {
        $this->logger->error($message, ['error' => $e->getMessage()]);
        throw new Exception("$message: " . $e->getMessage());
    }
}
