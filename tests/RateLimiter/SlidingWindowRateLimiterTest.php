<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\RateLimiter;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Storage\ArrayStorage;
use Vasoft\Joke\RateLimiter\SlidingWindowRateLimiter;
use Vasoft\Joke\RateLimiter\DefaultClientIdentifier;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Тесты для SlidingWindowRateLimiter.
 */
class SlidingWindowRateLimiterTest extends TestCase
{
    private SlidingWindowRateLimiter $limiter;
    private ArrayStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new ArrayStorage();
        $this->limiter = new SlidingWindowRateLimiter($this->storage);
    }

    public function testAllowRequestUnderLimit(): void
    {
        $clientKey = '192.168.1.1';
        $limit = 10;
        $window = 60;

        $result = $this->limiter->check($clientKey, $limit, $window);

        $this->assertTrue($result['allowed']);
        $this->assertEquals($limit - 1, $result['remaining']);
        $this->assertNotNull($result['resetAt']);
        $this->assertNull($result['retryAfter']);
    }

    public function testDenyRequestOverLimit(): void
    {
        $clientKey = '192.168.1.1';
        $limit = 3;
        $window = 60;

        for ($i = 0; $i < $limit; $i++) {
            $this->limiter->check($clientKey, $limit, $window);
        }

        $result = $this->limiter->check($clientKey, $limit, $window);

        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertNotNull($result['retryAfter']);
        $this->assertNotNull($result['resetAt']);
    }

    public function testWindowExpiration(): void
    {
        $clientKey = '192.168.1.1';
        $limit = 2;
        $window = 2;

        $this->limiter->check($clientKey, $limit, $window);
        
        sleep(3);

        $result = $this->limiter->check($clientKey, $limit, $window);

        $this->assertTrue($result['allowed']);
        $this->assertEquals($limit - 1, $result['remaining']);
    }

    public function testDifferentClientsHaveSeparateLimits(): void
    {
        $limit = 2;
        $window = 60;

        $this->limiter->check('192.168.1.1', $limit, $window);
        $this->limiter->check('192.168.1.1', $limit, $window);

        $result = $this->limiter->check('192.168.1.2', $limit, $window);

        $this->assertTrue($result['allowed']);
        $this->assertEquals($limit - 1, $result['remaining']);
    }

    public function testGetStats(): void
    {
        $clientKey = '192.168.1.1';
        $limit = 10;
        $window = 60;

        $this->limiter->check($clientKey, $limit, $window);
        $this->limiter->check($clientKey, $limit, $window);
        $this->limiter->check($clientKey, $limit, $window);

        $stats = $this->limiter->getStats($clientKey, $limit, $window);

        $this->assertEquals(3, $stats['current']);
        $this->assertEquals($limit, $stats['limit']);
        $this->assertEquals($window, $stats['window']);
        $this->assertNotNull($stats['resetAt']);
    }

    public function testReset(): void
    {
        $clientKey = '192.168.1.1';
        $limit = 10;
        $window = 60;

        $this->limiter->check($clientKey, $limit, $window);
        $this->limiter->check($clientKey, $limit, $window);

        $resetResult = $this->limiter->reset($clientKey);

        $this->assertTrue($resetResult);

        $result = $this->limiter->check($clientKey, $limit, $window);

        $this->assertTrue($result['allowed']);
        $this->assertEquals($limit - 1, $result['remaining']);
    }

    public function testClientIdentifier(): void
    {
        $clientIdentifier = new DefaultClientIdentifier();
        
        $request = new HttpRequest(
            get: [],
            post: [],
            cookies: [],
            files: [],
            server: [
                'REMOTE_ADDR' => '192.168.1.100',
                'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 192.168.1.100',
            ]
        );

        $clientKey = $clientIdentifier->identify($request);
        
        $this->assertEquals('10.0.0.1', $clientKey);
    }
}
