<?php

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;
use Vasoft\Joke\Core\Request\HttpRequest;

/**
 * Неблокирующая сессия. Считываются переменные и сразу закрывается
 */
class ReadonlySessionMiddleware implements MiddlewareInterface
{

    public function handle(HttpRequest $request, callable $next)
    {
        if (!$request->session->isStarted()) {
            session_start();
        }
        $request->session->load();
        session_write_close();
        return $next();
    }
}