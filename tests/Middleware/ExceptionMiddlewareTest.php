<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Middleware\ExceptionMiddleware;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\JsonResponse;
use Vasoft\Joke\Http\Response\ResponseStatus;
use Vasoft\Joke\Routing\Exceptions\NotFoundException;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Middleware\ExceptionMiddleware
 */
final class ExceptionMiddlewareTest extends TestCase
{
    public function testHandleSuccess(): void
    {
        $foo = static fn() => 'Success';
        $middleware = new ExceptionMiddleware();
        $output = $middleware->handle(new HttpRequest(), $foo);
        self::assertSame('Success', $output);
    }

    public function testHandleJokeException(): void
    {
        $foo = static function (): void {
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
        $foo = static function (): void {
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
