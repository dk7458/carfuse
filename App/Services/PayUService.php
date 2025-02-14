<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\DatabaseHelper; // new import
use Psr\Log\LoggerInterface; // ensure LoggerInterface is imported
require_once __DIR__ . '/../../config/payu.php';
/**
 * PayUService
 * 
 * Handles PayU API integration, including payment initialization, verification, and refunds.
 */
class PayUService
{
    private string $merchantKey;
    private string $merchantSalt;
    private string $endpoint;
    private $db; // DatabaseHelper instance
    private LoggerInterface $logger; // injected logger

    // Constructor updated to accept LoggerInterface
    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->merchantKey = $config['merchant_key'];
        $this->merchantSalt = $config['merchant_salt'];
        $this->endpoint = $config['endpoint'];
        $this->db = DatabaseHelper::getInstance();
        $this->logger = $logger;
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

        $response = Http::post("{$this->endpoint}/_payment", $params);
        throw_if($response->failed(), Exception::class, 'Payment API error');
        return [
            'status' => 'success',
            'data'   => $response->json()
        ];
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

        $response = Http::post("{$this->endpoint}/payment/verify", $params);
        throw_if($response->failed(), Exception::class, 'Payment verification error');
        return [
            'status' => 'success',
            'data'   => $response->json()
        ];
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
            $response = Http::post("{$this->endpoint}/refund", $params);
            throw_if($response->failed(), Exception::class, 'Refund processing error');
            $this->db->table('transaction_logs')->insert([
                'booking_id' => $transactionId,
                'amount'     => $amount,
                'type'       => 'refund',
                'status'     => 'completed'
            ]);
            $this->logger->info("[PayUService] Refund logged for transaction {$transactionId}");
            return [
                'status' => 'success',
                'data'   => $response->json()
            ];
        } catch (Exception $e) {
            $this->logger->error("[PayUService] Refund processing error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Refund processing failed'
            ];
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
