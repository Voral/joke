<?php

namespace Vasoft\Joke\Contract\Core\Middlewares;

use Vasoft\Joke\Core\Request\HttpRequest;

interface MiddlewareInterface
{
    public function handle(HttpRequest $request, callable $next);
}