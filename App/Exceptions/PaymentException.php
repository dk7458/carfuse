<?php

namespace App\Exceptions;

use Exception;

/**
 * Payment-specific exception class for standardized error handling
 * across payment services
 */
class PaymentException extends Exception
{
    public const PAYMENT_VALIDATION_ERROR = 100;
    public const PAYMENT_PROCESSING_ERROR = 200;
    public const PAYMENT_GATEWAY_ERROR = 300;
    public const REFUND_ERROR = 400;
    public const FRAUD_DETECTION_ERROR = 500;
    public const DATA_ERROR = 600;
    
    private array $context;
    
    /**
     * Constructor with error context
     *
     * @param string $message
     * @param int $code
     * @param array $context Additional context data for logging
     * @param Exception|null $previous
     */
    public function __construct(
        string $message, 
        int $code = 0, 
        array $context = [],
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    /**
     * Get error context data
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
