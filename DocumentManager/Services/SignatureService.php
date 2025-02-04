<?php

namespace DocumentManager\Services;

use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use DocumentManager\Services\FileStorage;
use App\Services\EncryptionService;

/**
 * Signature Service
 *
 * Manages electronic signatures both locally and via an external AES API.
 */
class SignatureService
{
    private Client $httpClient;
    private string $apiEndpoint;
    private string $apiKey;
    private FileStorage $fileStorage;
    private EncryptionService $encryptionService;
    private LoggerInterface $logger;

    public function __construct(
        Client $httpClient,
        array $config,
        FileStorage $fileStorage,
        EncryptionService $encryptionService,
        LoggerInterface $logger
    ) {
        if (empty($config['api_endpoint']) || empty($config['api_key'])) {
            throw new Exception('AES API configuration is incomplete.');
        }

        $this->httpClient = $httpClient;
        $this->apiEndpoint = $config['api_endpoint'];
        $this->apiKey = $config['api_key'];
        $this->fileStorage = $fileStorage;
        $this->encryptionService = $encryptionService;
        $this->logger = $logger;
    }

    /**
     * Upload a local signature.
     */
    public function uploadSignature(string $filePath, int $userId): string
    {
        $this->validateFileType($filePath);

        $encryptedContent = $this->encryptionService->encrypt(file_get_contents($filePath));
        $fileName = uniqid() . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
        $storagePath = $this->fileStorage->storeFile("signatures/{$userId}", $fileName, $encryptedContent, false);

        $this->logger->info("Signature uploaded", ['user_id' => $userId, 'path' => $storagePath]);
        return $storagePath;
    }

    /**
     * Send a document for AES signature.
     */
    public function sendForAdvancedSignature(string $filePath, int $userId, string $callbackUrl): array
    {
        try {
            $documentHash = hash_file('sha256', $filePath);

            $response = $this->httpClient->post("{$this->apiEndpoint}/sign-aes", [
                'headers' => $this->getAuthHeaders(),
                'multipart' => [
                    ['name' => 'file', 'contents' => fopen($filePath, 'r')],
                    ['name' => 'user_id', 'contents' => $userId],
                    ['name' => 'document_hash', 'contents' => $documentHash],
                    ['name' => 'callback_url', 'contents' => $callbackUrl],
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            $this->logAndThrow("Failed to send document for AES signing", $e);
        }
    }

    /**
     * Verify an AES signature.
     */
    public function verifyAdvancedSignature(string $signedFilePath, string $originalFilePath): bool
    {
        try {
            $response = $this->httpClient->post("{$this->apiEndpoint}/verify-aes", [
                'headers' => $this->getAuthHeaders(),
                'json' => [
                    'original_hash' => hash_file('sha256', $originalFilePath),
                    'signed_hash' => hash_file('sha256', $signedFilePath),
                ],
            ]);

            return json_decode($response->getBody(), true)['verified'] ?? false;
        } catch (Exception $e) {
            $this->logAndThrow("Failed to verify AES signature", $e);
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
            $response = $this->httpClient->get("{$this->apiEndpoint}/status/{$requestId}", [
                'headers' => $this->getAuthHeaders(),
            ]);

            return json_decode($response->getBody(), true);
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
            $response = $this->httpClient->get("{$this->apiEndpoint}/download/{$requestId}", [
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
