<?php
/**
 * File: config/documents.php
 * Purpose: Configuration for the Document Management System.
 */

return [
    // Storage Paths
    'storage' => [
        'user_documents' => __DIR__ . '/../../storage/documents/users/', // Path for user-specific documents
        'templates' => __DIR__ . '/../../storage/documents/templates/', // Path for document templates
        'terms_and_conditions' => __DIR__ . '/../../storage/documents/terms/', // Path for T&C documents
    ],

    // Encryption Settings
    'encryption' => [
        'aes_key' => getenv('DOCUMENT_AES_KEY') ?: 'default_secure_key', // AES Encryption Key
    ],

    // Document Types
    'allowed_types' => ['pdf', 'docx'], // Allowed document file types

    // Naming Conventions
    'naming' => [
        'contract_format' => 'contract_booking_{booking_id}_{timestamp}.pdf', // Format for contract names
        'invoice_format' => 'invoice_booking_{booking_id}_{timestamp}.pdf',  // Format for invoice names
    ],

    // Retention Policy
    'retention_policy' => [
        'contracts' => '10 years', // Retention period for contracts
        'invoices' => '7 years',   // Retention period for invoices
    ],
];
