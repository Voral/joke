<?php

namespace Vasoft\Joke\Tests\Core\Response;

use Vasoft\Joke\Core\Response\HtmlResponse;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Response\JsonResponse;

class HtmlResponseTest extends TestCase
{

    public function testGetBody()
    {
        $response = new HtmlResponse();
        $response->setBody('<html></html>');
        $body1 = $response->getBody();
        $body2 = $response->getBodyAsString();
        self::assertEquals('<html></html>', $body1);
        self::assertEquals('<html></html>', $body2);
    }

    public function testDefaultContentType(): void
    {
        $response = new HtmlResponse();
        $this->assertEquals('text/html', $response->headers->contentType);
    }
}
