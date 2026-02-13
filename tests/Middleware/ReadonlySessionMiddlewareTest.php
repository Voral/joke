<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Middleware;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Middleware\ReadonlySessionMiddleware;
use Vasoft\Joke\Http\HttpRequest;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Middleware\ReadonlySessionMiddleware
 */
final class ReadonlySessionMiddlewareTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testHandle(): void
    {
        self::assertSame(PHP_SESSION_NONE, session_status());
        new ReadonlySessionMiddleware()->handle(new HttpRequest(), static fn() => 'test');
        self::assertSame(PHP_SESSION_NONE, session_status());
    }
}
