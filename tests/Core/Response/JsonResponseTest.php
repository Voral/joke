<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core\Response;

use Vasoft\Joke\Core\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Response\ResponseStatus;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\Response\JsonResponse
 */
final class JsonResponseTest extends TestCase
{
    public function testDefaultStatusIsOk(): void
    {
        $response = new JsonResponse();
        self::assertSame(ResponseStatus::OK, $response->status);
    }

    public function testDefaultContentType(): void
    {
        $response = new JsonResponse();
        self::assertSame('application/json', $response->headers->contentType);
    }

    public function testGetBody(): void
    {
        $response = new JsonResponse();
        $body = [
            'example' => 'test',
            'value' => 1,
        ];
        $response->setBody($body);
        self::assertSame($body, $response->getBody());
        self::assertSame(json_encode($body), $response->getBodyAsString());
    }
}
