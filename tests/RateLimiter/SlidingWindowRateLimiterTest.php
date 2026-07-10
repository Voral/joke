<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\RateLimiter;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Contract\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\Contract\RateLimiter\RateLimiterInterface;
use Vasoft\Joke\Contract\Storage\StorageInterface;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\RateLimiter\RuleConfig;
use Vasoft\Joke\RateLimiter\SlidingWindowRateLimiter;
use Vasoft\Joke\Storage\ArrayStorage;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\RateLimiter\SlidingWindowRateLimiter
 */
final class SlidingWindowRateLimiterTest extends TestCase
{
    private ArrayStorage $storage;
    private SlidingWindowRateLimiter $rateLimiter;
    private HttpRequest $request;

    protected function setUp(): void
    {
        $this->storage = new ArrayStorage(1000);
        $identifier = new class implements ClientIdentifierInterface {
            public function identify(HttpRequest $request): string
            {
                return 'test:client';
            }
        };
        $this->rateLimiter = new SlidingWindowRateLimiter(
            $this->storage,
            $identifier,
            [
                'default' => new RuleConfig(limit: 5, windowSize: 10, name: 'default'),
                'api' => new RuleConfig(limit: 100, windowSize: 60, name: 'api'),
            ],
        );
        $this->request = new HttpRequest(server: ['REMOTE_ADDR' => '127.0.0.1']);
    }

    public function testFirstRequestIsAllowed(): void
    {
        $result = $this->rateLimiter->check($this->request, 'default');
        self::assertTrue($result->allowed);
        self::assertSame(5, $result->limit);
        self::assertSame(4, $result->remaining);
    }

    public function testRequestsWithinLimit(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $result = $this->rateLimiter->check($this->request, 'default');
            self::assertTrue($result->allowed);
        }
    }

    public function testRequestExceedsLimit(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $this->rateLimiter->check($this->request, 'default');
        }

        $result = $this->rateLimiter->check($this->request, 'default');
        self::assertFalse($result->allowed);
        self::assertSame(0, $result->remaining);
    }

    public function testDifferentRules(): void
    {
        $resultDefault = $this->rateLimiter->check($this->request, 'default');
        self::assertSame(5, $resultDefault->limit);

        $resultApi = $this->rateLimiter->check($this->request, 'api');
        self::assertSame(100, $resultApi->limit);
    }

    public function testUnknownRuleThrowsException(): void
    {
        $this->expectException(\Vasoft\Joke\RateLimiter\Exceptions\RateLimiterException::class);
        $this->rateLimiter->check($this->request, 'non_existent');
    }

    public function testWindowReset(): void
    {
        // Заполняем окно
        for ($i = 0; $i < 5; ++$i) {
            $this->rateLimiter->check($this->request, 'default');
        }

        // Перемещаем время вперёд на 2x windowSize
        $this->storage->setTime(1000 + 20);

        // После сброса окна запрос должен быть разрешён
        $result = $this->rateLimiter->check($this->request, 'default');
        self::assertTrue($result->allowed);
    }

    public function testRateLimitResultContainsIdentifier(): void
    {
        $result = $this->rateLimiter->check($this->request, 'default');
        self::assertSame('test:client', $result->identifier);
    }

    public function testRateLimitResultContainsResetTime(): void
    {
        $result = $this->rateLimiter->check($this->request, 'default');
        // resetTime должен быть больше текущего времени
        self::assertGreaterThan(time(), $result->resetTime);
    }
}