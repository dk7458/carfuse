<?php

/**
 * Logging Configuration
 */

return [
    'log_channel' => 'daily',  // Options: single, daily, syslog
    'log_path' => __DIR__ . '/../logs/app.log',
    'log_level' => 'debug',  // Options: debug, info, notice, warning, error, critical, alert, emergency
];
