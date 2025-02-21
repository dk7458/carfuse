<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use App\Services\FileStorage;
use App\Services\EncryptionService;
use App\Helpers\DatabaseHelper;
use App\Handlers\ExceptionHandler;

/**
 * Signature Service
 *
 * Manages electronic signatures both locally and via an external AES API.
 */
class SignatureService
{
    public const DEBUG_MODE = true;
    private LoggerInterface $logger;
    private $db;
    private string $apiEndpoint;
    private string $apiKey;
    private FileStorage $fileStorage;
    private EncryptionService $encryptionService;
    private ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        DatabaseHelper $db,
        array $config,
        FileStorage $fileStorage,
        EncryptionService $encryptionService,
        ExceptionHandler $exceptionHandler
    ) {
        if (empty($config['api_endpoint']) || empty($config['api_key'])) {
            throw new Exception('AES API configuration is incomplete.');
        }

        $this->logger = $logger;
        $this->db = $db;
        $this->apiEndpoint = $config['api_endpoint'];
        $this->apiKey = $config['api_key'];
        $this->fileStorage = $fileStorage;
        $this->encryptionService = $encryptionService;
        $this->exceptionHandler = $exceptionHandler;
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

        try {
            $this->db->table('signatures')->insert([
                'user_id'   => $userId,
                'file_path' => $storagePath,
                'encrypted' => true,
                'created_at'=> date('Y-m-d H:i:s'),
            ]);
            if (self::DEBUG_MODE) {
                $this->logger->info("[db] Signature record created", ['userId' => $userId, 'storagePath' => $storagePath]);
            }
        } catch (Exception $e) {
            $this->logger->error("[db] ❌ Database error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
        if (self::DEBUG_MODE) {
            $this->logger->info("[system] Signature uploaded", ['userId' => $userId, 'storagePath' => $storagePath]);
        }
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
            $this->logger->error("[api] Failed to send document for AES signing: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw new Exception("Failed to send document for AES signing: " . $e->getMessage());
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
            if (self::DEBUG_MODE) {
                $this->logger->info("[api] Signature verification", ['result' => $result]);
            }
            return $result['verified'] ?? false;
        } catch (Exception $e) {
            $this->logger->error("[api] Failed to verify signature: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
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
            $this->logger->error("[api] Failed to check AES signature status: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw new Exception("Failed to check AES signature status: " . $e->getMessage());
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
            $this->logger->error("[api] Failed to download AES signed document: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw new Exception("Failed to download AES signed document: " . $e->getMessage());
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
