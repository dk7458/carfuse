<?php

return [
    'signature_key' => env('SIGNATURE_KEY', 'your-signature-key'),
    'signature_secret' => env('SIGNATURE_SECRET', 'your-signature-secret'),
    'api_endpoint' => env('SIGNATURE_API_ENDPOINT', 'https://api.example.com'),
    'api_key' => env('SIGNATURE_API_KEY', 'your-api-key'),
    'allowed_extensions' => ['png', 'jpg', 'svg'],
    'max_file_size' => 2048, // in KB
];