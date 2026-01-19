<?php

namespace Vasoft\Joke\Tests\Core\Middlewares;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Vasoft\Joke\Core\Middlewares\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Request\HttpRequest;

class SessionMiddlewareTest extends TestCase
{

    #[RunInSeparateProcess]
    public function testHandle()
    {
        $salt = time();
        self::assertEquals(PHP_SESSION_NONE, session_status());
        new SessionMiddleware()->handle(new HttpRequest(), function (HttpRequest $request) use ($salt) {
            $request->session->set('example', $salt);
            return 'test';
        });
        self::assertEquals(PHP_SESSION_ACTIVE, session_status());
        self::assertSame($salt, $_SESSION['example']);
    }
}
