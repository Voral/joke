<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\RateLimiter;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Contract\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\RateLimiter\ClientIdentifier\ApiKeyClientIdentifier;
use Vasoft\Joke\RateLimiter\ClientIdentifier\ChainClientIdentifier;
use Vasoft\Joke\RateLimiter\ClientIdentifier\IpClientIdentifier;
use Vasoft\Joke\RateLimiter\ClientIdentifier\IpWithUserAgentIdentifier;
use Vasoft\Joke\RateLimiter\ClientIdentifier\UserIdClientIdentifier;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\RateLimiter\ClientIdentifier\IpClientIdentifier
 */
final class IpClientIdentifierTest extends TestCase
{
    public function testIdentifyFromRemoteAddr(): void
    {
        $request = new HttpRequest(server: ['REMOTE_ADDR' => '192.168.1.1']);
        $identifier = new IpClientIdentifier();
        self::assertSame('ip:192.168.1.1', $identifier->identify($request));
    }

    public function testIdentifyUnknown(): void
    {
        $request = new HttpRequest();
        $identifier = new IpClientIdentifier();
        self::assertSame('ip:unknown', $identifier->identify($request));
    }
}

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\RateLimiter\ClientIdentifier\IpWithUserAgentIdentifier
 */
final class IpWithUserAgentIdentifierTest extends TestCase
{
    public function testIdentifyWithUserAgent(): void
    {
        $request = new HttpRequest(
            server: ['REMOTE_ADDR' => '192.168.1.1', 'HTTP_USER_AGENT' => 'Mozilla/5.0'],
        );
        $identifier = new IpWithUserAgentIdentifier();
        $result = $identifier->identify($request);
        self::assertStringStartsWith('ip:192.168.1.1:ua:', $result);
    }

    public function testIdentifyWithoutUserAgent(): void
    {
        $request = new HttpRequest(server: ['REMOTE_ADDR' => '192.168.1.1']);
        $identifier = new IpWithUserAgentIdentifier();
        $result = $identifier->identify($request);
        self::assertStringStartsWith('ip:192.168.1.1:ua:', $result);
    }
}

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\RateLimiter\ClientIdentifier\UserIdClientIdentifier
 */
final class UserIdClientIdentifierTest extends TestCase
{
    public function testIdentifyWithUserId(): void
    {
        $request = new HttpRequest(server: ['REMOTE_ADDR' => '192.168.1.1']);
        $request->setProps(['user_id' => '42']);
        $identifier = new UserIdClientIdentifier();
        self::assertSame('user:42', $identifier->identify($request));
    }

    public function testIdentifyFallbackToIp(): void
    {
        $request = new HttpRequest(server: ['REMOTE_ADDR' => '10.0.0.1']);
        $identifier = new UserIdClientIdentifier();
        self::assertSame('ip:10.0.0.1', $identifier->identify($request));
    }
}

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\RateLimiter\ClientIdentifier\ApiKeyClientIdentifier
 */
final class ApiKeyClientIdentifierTest extends TestCase
{
    public function testIdentifyWithApiKey(): void
    {
        $request = new HttpRequest(
            server: ['HTTP_X_API_KEY' => 'secret-key-123'],
        );
        $identifier = new ApiKeyClientIdentifier();
        self::assertSame('apikey:secret-key-123', $identifier->identify($request));
    }

    public function testIdentifyFallbackToIp(): void
    {
        $request = new HttpRequest(server: ['REMOTE_ADDR' => '10.0.0.1']);
        $identifier = new ApiKeyClientIdentifier();
        self::assertSame('ip:10.0.0.1', $identifier->identify($request));
    }
}

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\RateLimiter\ClientIdentifier\ChainClientIdentifier
 */
final class ChainClientIdentifierTest extends TestCase
{
    public function testChainWithUserFirst(): void
    {
        $request = new HttpRequest(server: ['REMOTE_ADDR' => '10.0.0.1']);
        $request->setProps(['user_id' => '99']);

        $identifier = new ChainClientIdentifier([
            new UserIdClientIdentifier(),
            new IpClientIdentifier(),
        ]);
        self::assertSame('user:99', $identifier->identify($request));
    }

    public function testChainFallbackToIp(): void
    {
        $request = new HttpRequest(server: ['REMOTE_ADDR' => '10.0.0.1']);

        $identifier = new ChainClientIdentifier([
            new UserIdClientIdentifier(),
            new IpClientIdentifier(),
        ]);
        self::assertSame('ip:10.0.0.1', $identifier->identify($request));
    }

    public function testChainAllUnknown(): void
    {
        $request = new HttpRequest();
        $identifier = new ChainClientIdentifier([
            new UserIdClientIdentifier(),
            new IpClientIdentifier(),
        ]);
        self::assertSame('ip:unknown', $identifier->identify($request));
    }
}