<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core\Request;

use Vasoft\Joke\Core\Request\ServerCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\Request\ServerCollection
 */
final class ServerCollectionTest extends TestCase
{
    public function testParseHeaderFromServer(): void
    {
        $agent = 'Mozilla/5.0 ' . date('Y-m-d H:i:s');
        $serverCollection = new ServerCollection([
            'HTTP_USER_AGENT' => $agent,
            'HTTP_CUSTOM_HEADER' => 'My Custom Header',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => '100',
            'CONTENT_ENCODING' => 'gzip',
            'CONTENT_LANGUAGE' => 'en-US',
            'CONTENT_MD5' => 'QWxhZGRpbWVudA==',
        ]);
        $headers = $serverCollection->getHeaders();
        self::assertSame($agent, $headers['User-Agent'] ?? null);
        self::assertSame('My Custom Header', $headers['Custom-Header'] ?? null);
        self::assertSame('application/json', $headers['Content-Type'] ?? null);
        self::assertSame('100', $headers['Content-Length'] ?? null);
        self::assertSame('gzip', $headers['Content-Encoding'] ?? null);
        self::assertSame('en-US', $headers['Content-Language'] ?? null);
        self::assertSame('QWxhZGRpbWVudA==', $headers['Content-MD5'] ?? null);
    }

    public function testParseHeaderFromServerDefaultValues(): void
    {
        $serverCollection = new ServerCollection([]);
        $headers = $serverCollection->getHeaders();
        self::assertSame('text/html', $headers['Content-Type'] ?? null);
        self::assertSame('0', $headers['Content-Length'] ?? null);
        self::assertSame('', $headers['Content-Encoding'] ?? null);
        self::assertSame('', $headers['Content-Language'] ?? null);
        self::assertSame('', $headers['Content-MD5'] ?? null);
    }
}
