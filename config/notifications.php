<?php

return [
    'smtp_host' => 'smtp.example.com',
    'smtp_user' => 'user@example.com',
    'smtp_password' => 'password',
    'smtp_secure' => 'tls',
    'smtp_port' => 587,
    'from_email' => 'no-reply@example.com',
    'from_name' => 'CarFuse Notifications',
    'fcm_api_key' => 'YOUR_FCM_API_KEY_HERE',
    'notification_service' => env('NOTIFICATION_SERVICE', 'your-notification-service')
];
