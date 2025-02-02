<?php

namespace DocumentManager\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\EncryptionService;
use Illuminate\Http\UploadedFile;

/**
 * Signature Service
 *
 * Manages electronic signatures for documents.
 */
class SignatureService
{
    private Client $httpClient;
    private string $apiEndpoint;
    private string $apiKey;

    public function __construct(Client $httpClient, string $apiEndpoint, string $apiKey)
    {
        if (empty($apiEndpoint) || empty($apiKey)) {
            throw new Exception('Signature API configuration is incomplete.');
        }

        $this->httpClient = $httpClient;
        $this->apiEndpoint = $apiEndpoint;
        $this->apiKey = $apiKey;
    }

    /**
     * Send a document for signature.
     *
     * @param string $filePath The file path of the document to be signed.
     * @param array $signers List of signers with their details (name, email).
     * @param string $callbackUrl URL for signature completion callback.
     * @return array The response from the signature API.
     * @throws Exception If the request fails.
     */
    public function sendForSignature(string $filePath, array $signers, string $callbackUrl): array
    {
        try {
            $response = $this->httpClient->post("{$this->apiEndpoint}/send", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'multipart/form-data',
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                    ],
                    [
                        'name' => 'signers',
                        'contents' => json_encode($signers),
                    ],
                    [
                        'name' => 'callback_url',
                        'contents' => $callbackUrl,
                    ],
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            throw new Exception("Failed to send document for signature: " . $e->getMessage());
        }
    }

    /**
     * Check the status of a signature request.
     *
     * @param string $requestId The ID of the signature request.
     * @return array The response from the signature API.
     * @throws Exception If the request fails.
     */
    public function checkSignatureStatus(string $requestId): array
    {
        try {
            $response = $this->httpClient->get("{$this->apiEndpoint}/status/{$requestId}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            throw new Exception("Failed to check signature status: " . $e->getMessage());
        }
    }

    /**
     * Download a signed document.
     *
     * @param string $requestId The ID of the signature request.
     * @param string $outputPath The file path to save the signed document.
     * @return bool True on success, false otherwise.
     * @throws Exception If the request fails.
     */
    public function downloadSignedDocument(string $requestId, string $outputPath): bool
    {
        try {
            $response = $this->httpClient->get("{$this->apiEndpoint}/download/{$requestId}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
                'sink' => $outputPath, // Directly save the file
            ]);

            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            throw new Exception("Failed to download signed document: " . $e->getMessage());
        }
    }

    public function uploadSignature(UploadedFile $file, $userId) {
        // Validate file type
        $allowedExtensions = ['png', 'jpg', 'svg'];
        if (!in_array($file->getClientOriginalExtension(), $allowedExtensions)) {
            throw new \Exception('Invalid file type.');
        }

        // Encrypt and store the file
        $encryptedContent = EncryptionService::encrypt(file_get_contents($file->getRealPath()));
        $filePath = "signatures/{$userId}/" . uniqid() . '.' . $file->getClientOriginalExtension();
        Storage::put($filePath, $encryptedContent);

        // Log the upload
        Log::info("Signature uploaded for user {$userId} at {$filePath}");

        return $filePath;
    }

    public function verifySignature($signature, $userId) {
        // Retrieve stored signature
        $storedSignatures = Storage::files("signatures/{$userId}");
        foreach ($storedSignatures as $storedSignaturePath) {
            $storedSignature = EncryptionService::decrypt(Storage::get($storedSignaturePath));
            if ($storedSignature === $signature) {
                // Log the verification
                Log::info("Signature verified for user {$userId}");
                return true;
            }
        }

        // Log the failed verification
        Log::warning("Signature verification failed for user {$userId}");
        throw new \Exception('Signature verification failed.');
    }

    public function getSignature($userId) {
        // Retrieve stored signature
        $storedSignatures = Storage::files("signatures/{$userId}");
        if (empty($storedSignatures)) {
            throw new Exception('Signature not found.');
        }

        $signatures = [];
        foreach ($storedSignatures as $storedSignaturePath) {
            $signatures[] = EncryptionService::decrypt(Storage::get($storedSignaturePath));
        }

        // Log the access
        Log::info("Signatures retrieved for user: $userId");

        return $signatures;
    }
}
