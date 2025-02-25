<?php

use PHPUnit\Framework\TestCase;
use App\Services\RateLimiter;

class RateLimiterTest extends TestCase
{
    private $redis;

    protected function setUp(): void
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
        $this->redis->flushAll(); // Clean slate for tests
    }

    public function testIsRateLimited(): void
    {
        $rateLimiter = new RateLimiter($this->redis, 3, 60); // 3 attempts, 1-minute window
        $ip = '127.0.0.1';

        $this->assertFalse($rateLimiter->isRateLimited($ip));
        $rateLimiter->isRateLimited($ip);
        $rateLimiter->isRateLimited($ip);
        $this->assertTrue($rateLimiter->isRateLimited($ip));
    }

    public function testResetRateLimit(): void
    {
        $rateLimiter = new RateLimiter($this->redis, 3, 60);
        $ip = '127.0.0.1';

        $rateLimiter->isRateLimited($ip);
        $rateLimiter->isRateLimited($ip);
        $rateLimiter->reset($ip);
        $this->assertFalse($rateLimiter->isRateLimited($ip));
    }
}
