<?php
// ✅ 2. Initialize Logger First
require_once __DIR__ . '/logger.php';
$logger = getLogger('system');

// ✅ 3. Ensure Logger is Valid Before Continuing
if (!$logger instanceof Monolog\Logger) {
    error_log("❌ [BOOTSTRAP] Logger initialization failed. Using fallback logger.");
    $logger = new Monolog\Logger('fallback');
}

// ✅ 4. Load Environment Variables
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $container = require_once __DIR__ . '/config/dependencies.php';
    if (!$container instanceof \DI\Container) {
        throw new Exception("Dependency injection container initialization failed.");
    }
    $logger->info("✅ Dependencies initialized successfully.");
} catch (Exception $e) {
    $logger->critical("❌ Failed to initialize DI container: " . $e->getMessage());
    die("❌ DI container initialization failed: " . $e->getMessage() . "\n");
}

// ✅ 5. Load Security Helper
require_once __DIR__ . '/App/Helpers/SecurityHelper.php';

// ✅ 6. Load Dependency Injection Container (DI)
try {
    $container = require_once __DIR__ . '/config/dependencies.php';
    if (!$container instanceof \DI\Container) {
        throw new Exception("Dependency injection container initialization failed.");
    }
} catch (Exception $e) {
    $logger->critical("❌ Failed to initialize DI container: " . $e->getMessage());
    die("❌ DI container initialization failed: " . $e->getMessage() . "\n");
}

// ✅ 7. Register Logger in DI Container
$container->set('logger', fn() => getLogger('system'));

// ✅ 8. Load Database Instances
try {
    $database = $container->get('db');
    $secure_database = $container->get('secure_db');
} catch (Exception $e) {
    $logger->critical("❌ Failed to load database instances: " . $e->getMessage());
    die("❌ Database initialization failed: " . $e->getMessage() . "\n");
}

// ✅ 9. Load Required Configurations
define('BASE_PATH', __DIR__);
$configFiles = ['encryption', 'keymanager', 'filestorage'];
$config = [];

foreach ($configFiles as $file) {
    $path = BASE_PATH . "/config/{$file}.php";
    if (!file_exists($path)) {
        $logger->critical("❌ Missing configuration file: {$file}.php");
        continue;
    }
    $config[$file] = require $path;
}

$logger->info("✅ Configuration files loaded successfully.");

// ✅ 10. Validate Encryption Key
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    $logger->error("❌ Encryption key missing or invalid.");
    die("❌ Error: Encryption key missing or invalid in config/encryption.php\n");
}

// ✅ 11. Retrieve Critical Services from DI Container
try {
    $auditService = $container->get(\App\Services\AuditService::class);
    $encryptionService = $container->get(\App\Services\EncryptionService::class);
    $logger->info("✅ Critical services retrieved successfully.");
} catch (Exception $e) {
    $logger->critical("❌ Service retrieval failed: " . $e->getMessage());
    die("❌ Service retrieval failed: " . $e->getMessage() . "\n");
}

// ✅ 12. Validate Required Dependencies
$missingDependencies = [];
$requiredServices = [
    \App\Services\NotificationService::class,
    \App\Services\Auth\TokenService::class,
    \App\Services\Validator::class
];

foreach ($requiredServices as $service) {
    if (!$container->has($service)) {
        $logger->error("❌ Missing dependency: {$service}");
        $missingDependencies[] = $service;
    }
}

if (!empty($missingDependencies)) {
    $logger->warning("⚠️ Missing dependencies: " . implode(', ', $missingDependencies));
    echo "⚠️ Missing dependencies: " . implode(', ', $missingDependencies) . "\n";
    echo "⚠️ Ensure dependencies are correctly registered in config/dependencies.php.\n";
}

// ✅ 13. Secure Session Initialization Happens Last
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ 14. Final Configuration Return for Application Use
return [
    'db'              => $database,
    'secure_db'       => $secure_database,
    'logger'          => $logger,
    'auditService'    => $auditService,
    'encryptionService'=> $encryptionService,
    'container'       => $container,
];

