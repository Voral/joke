<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core\Middlewares;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Middlewares\ReadonlySessionMiddleware;
use Vasoft\Joke\Core\Request\HttpRequest;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\Middlewares\ReadonlySessionMiddleware
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
