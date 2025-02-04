<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
require_once __DIR__ . '/../../config/payu.php';
/**
 * PayUService
 * 
 * Handles PayU API integration, including payment initialization, verification, and refunds.
 */
class PayUService
{
    private Client $client;
    private LoggerInterface $logger;
    private string $merchantKey;
    private string $merchantSalt;
    private string $endpoint;

    public function __construct(Client $client, LoggerInterface $logger, array $config)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->merchantKey = $config['merchant_key'];
        $this->merchantSalt = $config['merchant_salt'];
        $this->endpoint = $config['endpoint'];
    }

    /**
     * Initialize a payment transaction
     *
     * @param string $transactionId
     * @param float $amount
     * @param string $productInfo
     * @param string $customerEmail
     * @param string $customerPhone
     * @return array
     */
    public function initiatePayment(string $transactionId, float $amount, string $productInfo, string $customerEmail, string $customerPhone): array
    {
        $hash = $this->generateHash($transactionId, $amount, $productInfo, $customerEmail);

        $params = [
            'key' => $this->merchantKey,
            'txnid' => $transactionId,
            'amount' => $amount,
            'productinfo' => $productInfo,
            'firstname' => $customerEmail, // Assuming first name is derived from the email
            'email' => $customerEmail,
            'phone' => $customerPhone,
            'surl' => $this->endpoint . '/success', // Success callback URL
            'furl' => $this->endpoint . '/failure', // Failure callback URL
            'hash' => $hash,
            'service_provider' => 'payu_paisa'
        ];

        try {
            $response = $this->client->post($this->endpoint . '/_payment', [
                'form_params' => $params,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->logger->error('PayU payment initialization failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Payment initialization failed'];
        }
    }

    /**
     * Verify a payment transaction
     *
     * @param string $transactionId
     * @return array
     */
    public function verifyPayment(string $transactionId): array
    {
        $params = [
            'key' => $this->merchantKey,
            'command' => 'verify_payment',
            'var1' => $transactionId,
        ];

        try {
            $response = $this->client->post($this->endpoint . '/payment/verify', [
                'form_params' => $params,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->logger->error('PayU payment verification failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Payment verification failed'];
        }
    }

    /**
     * Process a refund
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function processRefund(string $transactionId, float $amount): array
    {
        $params = [
            'key' => $this->merchantKey,
            'command' => 'refund_transaction',
            'var1' => $transactionId,
            'var2' => $amount,
        ];

        try {
            $response = $this->client->post($this->endpoint . '/refund', [
                'form_params' => $params,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->logger->error('PayU refund processing failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Refund processing failed'];
        }
    }

    /**
     * Generate hash for PayU API
     *
     * @param string $transactionId
     * @param float $amount
     * @param string $productInfo
     * @param string $customerEmail
     * @return string
     */
    private function generateHash(string $transactionId, float $amount, string $productInfo, string $customerEmail): string
    {
        $hashString = implode('|', [
            $this->merchantKey,
            $transactionId,
            $amount,
            $productInfo,
            $customerEmail,
            $this->merchantSalt
        ]);

        return hash('sha512', $hashString);
    }
}
