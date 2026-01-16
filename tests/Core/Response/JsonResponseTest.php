<?php

namespace Vasoft\Joke\Tests\Core\Response;

use Vasoft\Joke\Core\Collections\HeadersCollection;
use Vasoft\Joke\Core\Response\HtmlResponse;
use Vasoft\Joke\Core\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Response\ResponseStatus;

class JsonResponseTest extends TestCase
{
    public function testDefaultStatusIsOk(): void
    {
        $response = new JsonResponse();
        $this->assertEquals(ResponseStatus::OK, $response->status);
    }

    public function testDefaultContentType(): void
    {
        $response = new JsonResponse();
        $this->assertEquals('application/json', $response->headers->contentType);
    }

    public function testGetBody(): void
    {
        $response = new JsonResponse();
        $body = [
            'example' => 'test',
            'value' => 1
        ];
        $response->setBody($body);
        self::assertEquals($body, $response->getBody());
        self::assertEquals(json_encode($body), $response->getBodyAsString());
    }
}
