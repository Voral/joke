<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\RateLimiter;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Contract\RateLimiter\RateLimitResult;
use Vasoft\Joke\RateLimiter\RuleConfig;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\RateLimiter\RuleConfig
 */
final class RuleConfigTest extends TestCase
{
    public function testCreateRuleConfig(): void
    {
        $rule = new RuleConfig(limit: 100, windowSize: 60, name: 'api');
        self::assertSame(100, $rule->limit);
        self::assertSame(60, $rule->windowSize);
        self::assertSame('api', $rule->name);
    }

    public function testDefaultName(): void
    {
        $rule = new RuleConfig(limit: 10, windowSize: 1);
        self::assertSame('default', $rule->name);
    }
}

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Contract\RateLimiter\RateLimitResult
 */
final class RateLimitResultTest extends TestCase
{
    public function testCreateResult(): void
    {
        $result = new RateLimitResult(
            allowed: true,
            remaining: 95,
            limit: 100,
            resetTime: 1712345678,
            identifier: 'ip:127.0.0.1',
        );
        self::assertTrue($result->allowed);
        self::assertSame(95, $result->remaining);
        self::assertSame(100, $result->limit);
        self::assertSame(1712345678, $result->resetTime);
        self::assertSame('ip:127.0.0.1', $result->identifier);
    }

    public function testBlockedResult(): void
    {
        $result = new RateLimitResult(
            allowed: false,
            remaining: 0,
            limit: 100,
            resetTime: 1712345678,
            identifier: 'ip:127.0.0.1',
        );
        self::assertFalse($result->allowed);
        self::assertSame(0, $result->remaining);
    }
}