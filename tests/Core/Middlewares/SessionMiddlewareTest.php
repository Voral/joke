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
        self::assertEquals(PHP_SESSION_NONE, session_status());
        new SessionMiddleware()->handle(new HttpRequest(), fn() => 'test');
        self::assertEquals(PHP_SESSION_ACTIVE, session_status());
    }
}
