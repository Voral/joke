<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Middleware;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Vasoft\Joke\Middleware\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\HttpRequest;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Middleware\SessionMiddleware
 */
final class SessionMiddlewareTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testHandle(): void
    {
        $salt = time();
        self::assertSame(PHP_SESSION_NONE, session_status());
        new SessionMiddleware()->handle(new HttpRequest(), static function (HttpRequest $request) use ($salt) {
            $request->session->set('example', $salt);

            return 'test';
        });
        self::assertSame(PHP_SESSION_ACTIVE, session_status());
        self::assertSame($salt, $_SESSION['example']);
    }
}
