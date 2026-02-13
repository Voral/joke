<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response;

use Vasoft\Joke\Http\Response\HtmlResponse;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\HtmlResponse
 */
final class HtmlResponseTest extends TestCase
{
    public function testGetBody(): void
    {
        $response = new HtmlResponse();
        $response->setBody('<html></html>');
        $body1 = $response->getBody();
        $body2 = $response->getBodyAsString();
        self::assertSame('<html></html>', $body1);
        self::assertSame('<html></html>', $body2);
    }

    public function testDefaultContentType(): void
    {
        $response = new HtmlResponse();
        self::assertSame('text/html', $response->headers->contentType);
    }
}
