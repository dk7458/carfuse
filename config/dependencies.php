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
use GuzzleHttp\Client;

// Step 1: Log before DI container initialization
$logger = getLogger('system');
$logger->info("Step 1: Starting DI container initialization.");

// Step 2: Initialize DI Container
try {
    $container = new Container();
    $logger->info("Step 2: DI Container created successfully.");
} catch (Exception $e) {
    $logger->error("❌ [DI] Failed to initialize DI container: " . $e->getMessage());
    die("❌ Dependency Injection container failed: " . $e->getMessage() . "\n");
}
$container->set('logger', fn() => $logger);

// Step 3: Load configuration files.
$logger->info("Step 3: Loading configuration files.");
$configDirectory = __DIR__;
$config = [];
foreach (glob("{$configDirectory}/*.php") as $filePath) {
    $fileName = basename($filePath, '.php');
    if ($fileName !== 'dependencies') {
        $config[$fileName] = require $filePath;
        $logger->info("Configuration file loaded: {$fileName}.php");
    }
}

// Step 4: Ensure required directories exist.
$templateDirectory = __DIR__ . '/../storage/templates';
$fileStorageConfig = $config['filestorage'] ?? [];
if (!is_dir($templateDirectory)) {
    mkdir($templateDirectory, 0775, true);
}
if (!empty($fileStorageConfig['base_directory']) && !is_dir($fileStorageConfig['base_directory'])) {
    mkdir($fileStorageConfig['base_directory'], 0775, true);
}
$logger->info("Step 4: Required directories verified.");

// Step 5: Initialize EncryptionService.
$encryptionService = new EncryptionService($config['encryption']['encryption_key'] ?? '');
$container->set(EncryptionService::class, fn() => $encryptionService);
$logger->info("Step 5: EncryptionService registered.");

// Step 6: Initialize FileStorage using centralized logger.
$fileStorage = new FileStorage(getLogger('api'), $fileStorageConfig, $encryptionService);
$container->set(FileStorage::class, fn() => $fileStorage);
$logger->info("Step 6: FileStorage registered.");

// Step 7: Load key manager configuration.
$config['keymanager'] = require __DIR__ . '/keymanager.php';
$logger->info("Step 7: Key Manager configuration loaded.");

// Step 8: Initialize DatabaseHelper instances.
try {
    $database = DatabaseHelper::getInstance();
    $secure_database = DatabaseHelper::getSecureInstance();
    getLogger('db')->info("✅ Both databases initialized successfully.");
} catch (Exception $e) {
    getLogger('db')->error("❌ Database initialization failed: " . $e->getMessage());
    die("❌ Database initialization failed. Check logs for details.\n");
}
$container->set('db', fn() => $database);
$container->set('secure_db', fn() => $secure_database);
$logger->info("Step 8: Database services registered.");

// Step 9: Register services with centralized logger calls.
$container->set(Validator::class, fn() => new Validator(getLogger('api')));
$container->set(RateLimiter::class, fn() => new RateLimiter(getLogger('db'), $database));
$container->set(AuditService::class, fn() => new AuditService(getLogger('security')));
$container->set(TokenService::class, fn() => new TokenService($_ENV['JWT_SECRET'] ?? '', $_ENV['JWT_REFRESH_SECRET'] ?? '', getLogger('auth')));
$container->set(NotificationService::class, fn() => new NotificationService(getLogger('api'), $config['notifications'] ?? [], $database));
$container->set(UserService::class, fn() => new UserService(getLogger('auth'), $database, $config['encryption']['jwt_secret'] ?? ''));
$container->set(PaymentService::class, fn() => new PaymentService(getLogger('db'), $database, new Payment(), getenv('PAYU_API_KEY') ?: '', getenv('PAYU_API_SECRET') ?: ''));
$container->set(BookingService::class, fn() => new BookingService(getLogger('api'), $database));
$container->set(MetricsService::class, fn() => new MetricsService(getLogger('api'), $database));
$container->set(ReportService::class, fn() => new ReportService(getLogger('api'), $database));
$container->set(RevenueService::class, fn() => new RevenueService(getLogger('db'), $database));
$container->set(SignatureService::class, fn() => new SignatureService($config['signature'], $fileStorage, $encryptionService, getLogger('security')));
$container->set(DocumentService::class, fn() => new DocumentService(
    getLogger('api'),
    $container->get(AuditService::class),
    $fileStorage,
    $encryptionService,
    $container->get(TemplateService::class)
));
$container->set(AuthService::class, fn() => new AuthService(getLogger('auth'), $database, $config['encryption']));
$container->set(KeyManager::class, fn() => new KeyManager(getLogger('security'), $config['keymanager']['keys'] ?? []));
$logger->info("Step 9: Service registration completed.");

return $container;
