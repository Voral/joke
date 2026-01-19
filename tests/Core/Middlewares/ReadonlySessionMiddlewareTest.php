<?php

namespace Vasoft\Joke\Tests\Core\Middlewares;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Middlewares\ReadonlySessionMiddleware;
use Vasoft\Joke\Core\Request\HttpRequest;

class ReadonlySessionMiddlewareTest extends TestCase
{

    #[RunInSeparateProcess]
    public function testHandle()
    {
        self::assertEquals(PHP_SESSION_NONE, session_status());
        new ReadonlySessionMiddleware()->handle(new HttpRequest(), fn() => 'test');
        self::assertEquals(PHP_SESSION_NONE, session_status());
    }
}
