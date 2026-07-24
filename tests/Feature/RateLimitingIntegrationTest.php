<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Application\Application;
use Vasoft\Joke\Provider\RateLimiterServiceProvider;
use Vasoft\Joke\Config\RateLimitConfig;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\ServerCollection;
use Vasoft\Joke\RateLimiter\RateLimitMiddleware;
use Vasoft\Joke\RateLimiter\SlidingWindowRateLimiter;
use Vasoft\Joke\Storage\ArrayStorage;

/**
 * Интеграционные тесты Rate Limiting.
 */
class RateLimitingIntegrationTest extends TestCase
{
    private ArrayStorage $storage;
    private SlidingWindowRateLimiter $limiter;

    protected function setUp(): void
    {
        $this->storage = new ArrayStorage();
        $this->limiter = new SlidingWindowRateLimiter($this->storage);
    }

    public function testRateLimitingMiddleware(): void
    {
        $clientKey = '192.168.1.1';
        $limit = 3;
        $window = 60;

        // Make requests up to the limit
        for ($i = 0; $i < $limit; $i++) {
            $request = $this->createRequest($clientKey);
            $result = $this->limiter->check($clientKey, $limit, $window);
            
            $this->assertTrue($result['allowed'], "Request {$i} should be allowed");
        }

        // Next request should be denied
        $result = $this->limiter->check($clientKey, $limit, $window);
        
        $this->assertFalse($result['allowed'], '4th request should be denied');
        $this->assertEquals(0, $result['remaining']);
        $this->assertNotNull($result['retryAfter']);
    }

    public function testRateLimitingWithMiddleware(): void
    {
        $clientKey = '192.168.1.2';
        $limit = 2;
        $window = 60;

        // Create middleware with custom params
        $middleware = RateLimitMiddleware::fromParams(
            $this->limiter,
            new \Vasoft\Joke\RateLimiter\DefaultClientIdentifier(),
            "$limit,$window"
        );

        // First request
        $request1 = $this->createRequest($clientKey);
        $response1 = $middleware->handle($request1, fn($req) => new \Vasoft\Joke\Http\Response\JsonResponse(['status' => 'ok']));
        
        $this->assertEquals(200, $response1->status);

        // Second request
        $request2 = $this->createRequest($clientKey);
        $response2 = $middleware->handle($request2, fn($req) => new \Vasoft\Joke\Http\Response\JsonResponse(['status' => 'ok']));
        
        $this->assertEquals(200, $response2->status);

        // Third request should be denied
        $request3 = $this->createRequest($clientKey);
        $response3 = $middleware->handle($request3, fn($req) => new \Vasoft\Joke\Http\Response\JsonResponse(['status' => 'ok']));
        
        $this->assertEquals(429, $response3->status);
    }

    public function testDifferentClientsHaveSeparateLimits(): void
    {
        $limit = 2;
        $window = 60;

        // Client 1 reaches limit
        for ($i = 0; $i < $limit; $i++) {
            $this->limiter->check('192.168.1.1', $limit, $window);
        }

        // Client 2 should still be allowed
        $result = $this->limiter->check('192.168.1.2', $limit, $window);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals($limit - 1, $result['remaining']);
    }

    /**
     * Creates a test request with specified client IP.
     */
    private function createRequest(string $clientIp): HttpRequest
    {
        return new HttpRequest(
            get: [],
            post: [],
            cookies: [],
            files: [],
            server: [
                'REMOTE_ADDR' => $clientIp,
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/api/test',
                'HTTP_HOST' => 'localhost',
            ],
            rawBody: ''
        );
    }
}
