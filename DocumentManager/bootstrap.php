<?php

use DocumentManager\Services\EncryptionService;

$config = require __DIR__ . '/config/encryption.php';

$encryptionService = new EncryptionService($config);
