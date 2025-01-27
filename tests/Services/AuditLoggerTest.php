<?php

use PHPUnit\Framework\TestCase;
use App\Services\AuditLogger;
use Psr\Log\LoggerInterface;

class AuditLoggerTest extends TestCase
{
    public function testLog(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('Audit Event: UserCreated'),
                $this->equalTo(['user_id' => 123])
            );

        $auditLogger = new AuditLogger($mockLogger);
        $auditLogger->log('UserCreated', ['user_id' => 123]);
    }
}
