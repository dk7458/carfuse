<?php
/**
 * Fraud detection configuration
 */
return [
    // Risk thresholds for different risk levels
    'thresholds' => [
        'high' => 70,
        'medium' => 50,
        'low' => 30
    ],
    
    // Fraud indicator weights for scoring
    'indicator_weights' => [
        'high_amount' => 15,
        'multiple_attempts' => 20,
        'unusual_location' => 30,
        'address_mismatch' => 25,
        'card_country_mismatch' => 35,
        'rapid_transactions' => 18,
        'unusual_time' => 10,
        'ip_proxy_detected' => 40,
        'device_mismatch' => 28,
        'risky_email_domain' => 15
    ],
    
    // Rule parameters
    'rules' => [
        'high_amount_threshold' => 1000,
        'max_payment_attempts' => 3,
        'min_transaction_interval_minutes' => 5,
        'business_hours_start' => 6,
        'business_hours_end' => 23,
        'high_frequency_threshold' => 5 // transactions per hour
    ],
    
    // Risky email domains
    'risky_email_domains' => [
        'tempmail.com',
        'throwaway.com',
        'mailinator.com',
        'guerrillamail.com',
        'yopmail.com',
        'sharklasers.com',
        'dispostable.com',
        '10minutemail.com',
        'temp-mail.org'
    ],
    
    // Recommendations based on risk level
    'recommendations' => [
        'high' => 'block_transaction',
        'medium' => 'additional_verification',
        'low' => 'flag_for_review',
        'minimal' => 'proceed'
    ],
    
    // Custom rules can be added here
    'custom_rules' => [
        // Example of a custom rule:
        /*
        'suspicious_ip_range' => [
            'weight' => 25,
            'condition' => function($data) {
                // Check if IP is in suspicious range
                if (isset($data['ip_address'])) {
                    return strpos($data['ip_address'], '192.168.') === 0;
                }
                return false;
            }
        ]
        */
    ]
];
