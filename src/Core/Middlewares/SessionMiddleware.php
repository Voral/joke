<?php

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;
use Vasoft\Joke\Core\Request\HttpRequest;

class SessionMiddleware implements MiddlewareInterface
{

    public function handle(HttpRequest $request, callable $next)
    {
        if (PHP_SESSION_ACTIVE !== session_status()) {
            session_start();
        }
        return $next($request);
    }
}