<?php

namespace Vasoft\Joke\Tests\Core\Middlewares;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Middlewares\ExceptionMiddleware;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Response\JsonResponse;
use Vasoft\Joke\Core\Response\ResponseStatus;
use Vasoft\Joke\Core\Routing\Exceptions\NotFoundException;

class ExceptionMiddlewareTest extends TestCase
{

    public function testHandleSuccess(): void
    {
        $foo = static fn() => 'Success';
        $middleware = new ExceptionMiddleware();
        $output = $middleware->handle(new HttpRequest(), $foo);
        self::assertEquals('Success', $output);
    }

    public function testHandleJokeException(): void
    {
        $foo = static function () {
            throw new NotFoundException('Route not found');
        };
        $middleware = new ExceptionMiddleware();
        $output = $middleware->handle(new HttpRequest(), $foo);
        self::assertInstanceOf(JsonResponse::class, $output);
        self::assertSame(ResponseStatus::NOT_FOUND, $output->status);
        self::assertSame([
            'message' => 'Route not found',
        ], $output->getBody());
    }

    public function testHandlePHPException(): void
    {
        $foo = static function () {
            throw new \Exception('Some exception');
        };
        $middleware = new ExceptionMiddleware();
        $output = $middleware->handle(new HttpRequest(), $foo);
        self::assertInstanceOf(JsonResponse::class, $output);
        self::assertSame(ResponseStatus::INTERNAL_SERVER_ERROR, $output->status);
        self::assertSame([
            'message' => 'Some exception',
        ], $output->getBody());
    }
}
