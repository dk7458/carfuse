<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\UserController;
use App\Services\Validator;
use App\Services\RateLimiter;
use App\Services\AuditLogger;
use App\Services\NotificationService;

class UserControllerTest extends TestCase
{
    private $controller;

    protected function setUp(): void
    {
        $db = $this->createMock(PDO::class);
        $logger = $this->createMock(Psr\Log\LoggerInterface::class);
        $validator = new Validator();
        $rateLimiter = $this->createMock(RateLimiter::class);
        $auditLogger = $this->createMock(AuditLogger::class);
        $notificationService = $this->createMock(NotificationService::class);

        $this->controller = new UserController(
            $db, $logger, ['jwt_secret' => 'test_secret'], $validator, $rateLimiter, $auditLogger, $notificationService
        );
    }

    public function testRegisterValidData(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'phone' => '1234567890',
            'address' => '123 Main St',
        ];

        $response = $this->controller->register($data);

        $this->assertEquals('success', $response['status']);
    }

    public function testRegisterInvalidEmail(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'Password123',
            'phone' => '1234567890',
            'address' => '123 Main St',
        ];

        $response = $this->controller->register($data);

        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('errors', $response);
    }
}
