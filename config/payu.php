<?php
return [
    'api_key' => env('PAYU_API_KEY', 'your-api-key'),
    'api_secret' => env('PAYU_API_SECRET', 'your-api-secret'),
    'merchant_id' => env('PAYU_MERCHANT_ID', 'your-merchant-id'),
    'endpoint' => 'https://secure.payu.com/api',
    'merchant_key' => 'your-merchant-key',
    'merchant_salt' => 'your-merchant-salt',
];
