<?php
// General settings
define('ENVIRONMENT', 'development'); // Change to 'production' for live
define('BASE_PATH', __DIR__ . '/../');
define('APP_URL', 'http://localhost/project_root'); // Base URL of the app

// Database credentials
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'project_db');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

// Logging settings
define('LOG_PATH', BASE_PATH . 'logs/');

// Email settings
define('EMAIL_FROM', 'no-reply@example.com');
define('EMAIL_SUPPORT', 'support@example.com');
