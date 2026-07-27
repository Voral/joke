<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\RateLimit;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\RateLimit\IpClientIdentifier;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\RateLimit\IpClientIdentifier
 */
final class IpClientIdentifierTest extends TestCase
{
    public function testDirectIp(): void
    {
        $identifier = new IpClientIdentifier();
        $request = new HttpRequest(server: ['REMOTE_ADDR' => '192.168.1.1']);

        $id = $identifier->identify($request);

        self::assertSame('192.168.1.1', $id);
    }

    public function testTrustedProxyWithForwarded(): void
    {
        $identifier = new IpClientIdentifier(['127.0.0.1']);
        $request = new HttpRequest(
            server: ['REMOTE_ADDR' => '127.0.0.1'],
        );
        $request->headers->set('X-Forwarded-For', '203.0.113.42, 203.0.113.41');

        $id = $identifier->identify($request);

        self::assertSame('203.0.113.42', $id);
    }

    public function testUntrustedProxyIgnoresForwarded(): void
    {
        $identifier = new IpClientIdentifier(['127.0.0.1']);
        $request = new HttpRequest(
            server: ['REMOTE_ADDR' => '203.0.113.100'],
        );
        $request->headers->set('X-Forwarded-For', '203.0.113.42');

        $id = $identifier->identify($request);

        self::assertSame('203.0.113.100', $id);
    }

    public function testTrustedProxyNoForwarded(): void
    {
        $identifier = new IpClientIdentifier(['10.0.0.1']);
        $request = new HttpRequest(server: ['REMOTE_ADDR' => '10.0.0.1']);

        $id = $identifier->identify($request);

        self::assertSame('10.0.0.1', $id);
    }

    public function testMultipleTrustedProxies(): void
    {
        $identifier = new IpClientIdentifier(['10.0.0.1', '10.0.0.2']);

        $request1 = new HttpRequest(server: ['REMOTE_ADDR' => '10.0.0.1']);
        $request1->headers->set('X-Forwarded-For', '192.168.1.1');

        $request2 = new HttpRequest(server: ['REMOTE_ADDR' => '10.0.0.2']);
        $request2->headers->set('X-Forwarded-For', '192.168.1.2');

        self::assertSame('192.168.1.1', $identifier->identify($request1));
        self::assertSame('192.168.1.2', $identifier->identify($request2));
    }

    public function testUnknownRemoteAddr(): void
    {
        $identifier = new IpClientIdentifier();
        $request = new HttpRequest();

        $id = $identifier->identify($request);

        self::assertSame('unknown', $id);
    }

    public function testForwardedChainFirstIp(): void
    {
        $identifier = new IpClientIdentifier(['203.0.113.1']);
        $request = new HttpRequest(server: ['REMOTE_ADDR' => '203.0.113.1']);
        $request->headers->set('X-Forwarded-For', '198.51.100.1, 198.51.100.2, 198.51.100.3');

        $id = $identifier->identify($request);

        self::assertSame('198.51.100.1', $id);
    }
}
