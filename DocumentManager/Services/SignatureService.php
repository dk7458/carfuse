<?php

namespace DocumentManager\Services;

use Exception;
use GuzzleHttp\Client;

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
}
