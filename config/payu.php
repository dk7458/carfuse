<?php
return [
    'api_key' => env('PAYU_API_KEY', 'your-api-key'),
    'api_secret' => env('PAYU_API_SECRET', 'your-api-secret'),
    'endpoint' => 'https://secure.payu.com/api',
    'merchant_key' => 'your-merchant-key',
    'merchant_salt' => 'your-merchant-salt',
];
