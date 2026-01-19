<?php

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;
use Vasoft\Joke\Core\Request\HttpRequest;

class SessionMiddleware implements MiddlewareInterface
{

    public function handle(HttpRequest $request, callable $next)
    {
        if (!$request->session->isStarted()) {
            session_start();
        }
        $request->session->load();
        $result = $next($request);
        $request->session->save();
        return $result;
    }
}