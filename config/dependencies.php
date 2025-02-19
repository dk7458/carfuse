<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use App\Helpers\DatabaseHelper;
use App\Services\Validator;
use App\Services\RateLimiter;
use App\Services\Auth\TokenService;
use App\Services\Auth\AuthService;
use App\Services\NotificationService;
use App\Services\UserService;
use App\Services\PaymentService;
use App\Services\BookingService;
use App\Services\MetricsService;
use App\Services\ReportService;
use App\Services\RevenueService;
use App\Services\EncryptionService;
use App\Services\Security\KeyManager;
use App\Queues\NotificationQueue;
use App\Queues\DocumentQueue;
use App\Services\DocumentService;
use App\Services\FileStorage;
use App\Services\TemplateService;
use App\Services\SignatureService;
use App\Services\AuditService;
use App\Models\Payment;
use App\Services\DatabaseService;
use App\Services\SecurityService;
use GuzzleHttp\Client;

// Step 1: Initialize DI Container
try {
    $container = new Container();
    // Register categorized loggers.
    $container->set('logger', fn() => getLogger('system'));
    $container->set('auth_logger', fn() => getLogger('auth'));
    $container->set('db_logger', fn() => getLogger('db'));
    $container->set('api_logger', fn() => getLogger('api'));
    $container->set('security_logger', fn() => getLogger('security'));
    // Register new dependencies logger.
    $container->set('dependencies_logger', fn() => getLogger('dependencies'));
    $container->get('dependencies_logger')->info("🔄 Step 1: Starting Dependency Injection.");
    $container->get('logger')->info("Step 1: DI Container created and loggers registered.");
} catch (Exception $e) {
    getLogger('system')->error("❌ [DI] Failed to initialize DI container: " . $e->getMessage());
    die("❌ Dependency Injection container failed: " . $e->getMessage() . "\n");
}

// Step 2: Load configuration files.
$container->get('logger')->info("Step 2: Loading configuration files.");
$configDirectory = __DIR__;
$config = [];
foreach (glob("{$configDirectory}/*.php") as $filePath) {
    $fileName = basename($filePath, '.php');
    if ($fileName !== 'dependencies') {
        $config[$fileName] = require $filePath;
        $container->get('logger')->info("Configuration file loaded: {$fileName}.php");
    }
}

// Step 3: Ensure required directories exist.
$templateDirectory = __DIR__ . '/../storage/templates';
$fileStorageConfig = $config['filestorage'] ?? [];
if (!is_dir($templateDirectory)) {
    mkdir($templateDirectory, 0775, true);
}
if (!empty($fileStorageConfig['base_directory']) && !is_dir($fileStorageConfig['base_directory'])) {
    mkdir($fileStorageConfig['base_directory'], 0775, true);
}
$container->get('logger')->info("Step 3: Required directories verified.");

// Step 4: Initialize EncryptionService.
$encryptionService = new EncryptionService($config['encryption']['encryption_key'] ?? '');
$container->set(EncryptionService::class, fn() => $encryptionService);
$container->get('logger')->info("Step 4: EncryptionService registered.");

// Step 5: Initialize FileStorage using centralized logger.
if (!isset($config['filestorage']) || !is_array($config['filestorage'])) {
    $container->get('logger')->critical("❌ FileStorage configuration is missing or invalid.");
    die("❌ FileStorage configuration is missing or invalid.\n");
}
$container->set(FileStorage::class, function () use ($container, $config) {
    return new FileStorage(
        $config['filestorage'],  // Pass correct config as an array.
        $container->get('api_logger'), // Pass the proper logger.
        $container->get(EncryptionService::class) // Inject EncryptionService.
    );
});
$container->get('logger')->info("Step 5: FileStorage registered.");

// Step 6: Load key manager configuration.
$config['keymanager'] = require __DIR__ . '/keymanager.php';
$container->get('logger')->info("Step 6: Key Manager configuration loaded.");

// Step 7: Initialize DatabaseHelper instances BEFORE services that depend on them.
try {
    $database = DatabaseHelper::getInstance();
    $secure_database = DatabaseHelper::getSecureInstance();
    $container->get('db_logger')->info("✅ Both databases initialized successfully.");
} catch (Exception $e) {
    $container->get('db_logger')->error("❌ Database initialization failed: " . $e->getMessage());
    die("❌ Database initialization failed. Check logs for details.\n");
}
$container->set('db', fn() => $database);
$container->set('secure_db', fn() => $secure_database);
$container->get('logger')->info("Step 7: Database services registered.");

// Debug database connection before proceeding.
try {
    $pdo = $container->get('db')->getConnection()->getPdo();
    if (!$pdo) {
        throw new Exception("Database connection failed.");
    }
    $container->get('db_logger')->info("✅ Database connection verified successfully.");
} catch (Exception $e) {
    $container->get('db_logger')->critical("❌ Database connection verification failed: " . $e->getMessage());
    die("❌ Database connection issue: " . $e->getMessage() . "\n");
}

// Step 8: Register services with proper dependency order.
$container->set(Validator::class, fn() => new Validator($container->get('api_logger')));
$container->set(RateLimiter::class, fn() => new RateLimiter($container->get('db_logger'), $container->get('db')));
$container->set(AuditService::class, fn() => new AuditService($container->get('security_logger')));
$container->set(TokenService::class, fn() => new TokenService(
    $_ENV['JWT_SECRET'] ?? '',
    $_ENV['JWT_REFRESH_SECRET'] ?? '',
    $container->get('auth_logger')
));
// Ensure AuthService is passed the container-registered database.
$container->set(AuthService::class, fn() => new AuthService(
    $container->get('auth_logger'),
    $container->get('db'),
    $config['encryption']
));
$container->set(UserService::class, fn() => new UserService(
    $container->get('auth_logger'),
    $container->get('db'),
    $config['encryption']['jwt_secret'] ?? ''
));
// Register external API–dependent services after core ones.
$container->set(NotificationService::class, fn() => new NotificationService(
    $container->get('api_logger'),
    $config['notifications'] ?? [],
    $container->get('db')
));
$container->set(PaymentService::class, fn() => new PaymentService(
    $container->get('db_logger'),
    $container->get('db'),
    new Payment(),
    getenv('PAYU_API_KEY') ?: '',
    getenv('PAYU_API_SECRET') ?: ''
));
$container->set(BookingService::class, fn() => new BookingService(
    $container->get('api_logger'),
    $container->get('db')
));
$container->set(MetricsService::class, fn() => new MetricsService(
    $container->get('api_logger'),
    $container->get('db')
));
$container->set(ReportService::class, fn() => new ReportService(
    $container->get('api_logger'),
    $container->get('db')
));
$container->set(RevenueService::class, fn() => new RevenueService(
    $container->get('db_logger'),
    $container->get('db')
));
$container->set(SignatureService::class, fn() => new SignatureService(
    $config['signature'],
    $fileStorage,
    $encryptionService,
    $container->get('security_logger')
));
$container->set(DocumentService::class, fn() => new DocumentService(
    $container->get('api_logger'),
    $container->get(AuditService::class),
    $fileStorage,
    $encryptionService,
    $container->get(TemplateService::class)
));

// New registrations for additional services ensuring proper logging.
$container->set(DatabaseService::class, fn() => new DatabaseService($container->get('db_logger')));
$container->set(SecurityService::class, fn() => new SecurityService($container->get('security_logger')));

$container->get('logger')->info("Step 8: Service registration completed.");

// Step 9: Final check for required service registrations and circular dependency detection.
$requiredServices = [
    TokenService::class,
    AuthService::class,
    Validator::class,
    DatabaseService::class,
    SecurityService::class
];
$container->get('dependencies_logger')->info("🔄 Step 9: Checking for circular dependencies...");
foreach ($requiredServices as $service) {
    try {
        $container->get($service);
        $container->get('dependencies_logger')->info("✅ Service loaded successfully: {$service}");
    } catch (Exception $e) {
        $container->get('dependencies_logger')->critical("❌ Service failed to load: {$service}", ['trace' => $e->getTraceAsString()]);
        die("❌ Service failure: {$service}: " . $e->getMessage() . "\n");
    }
}

$container->get('dependencies_logger')->info("✅ DI container validation completed successfully.");
// Ensure container integrity before return.
return [
    'db'                => $container->get('db'),
    'secure_db'         => $container->get('secure_db'),
    'logger'            => $container->get('logger'),
    'auditService'      => $container->get(AuditService::class),
    'encryptionService' => $container->get(EncryptionService::class),
    'container'         => $container,
];
