<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response;

use phpmock\phpunit\PHPMock;
use Vasoft\Joke\Http\Response\HtmlResponse;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\HtmlResponse
 */
final class HtmlResponseTest extends TestCase
{
    use PHPMock;

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

    public function testSendCookies(): void
    {
        $headers = [];
        $mockHeader = self::getFunctionMock('Vasoft\Joke\Http\Response', 'header');
        $mockHeader->expects(self::exactly(4))->willReturnCallback(static function (string $value) use (&$headers): void {
            $headers[] = $value;
        });

        $response = new HtmlResponse();
        $response->cookies->add('cookie1', 'value1');
        $response->cookies->add('cookie2', 'value2');

        $response->send();

        $hasCookie1 = false;
        $hasCookie2 = false;

        foreach ($headers as $header) {
            if (str_starts_with($header, 'Set-Cookie: cookie1=value1;')) {
                $hasCookie1 = true;
            }
            if (str_starts_with($header, 'Set-Cookie: cookie2=value2;')) {
                $hasCookie2 = true;
            }
        }

        self::assertTrue($hasCookie1, 'The Set-Cookie header for cookie1 is missing');
        self::assertTrue($hasCookie2, 'The Set-Cookie header for cookie2 is missing');
    }
}
