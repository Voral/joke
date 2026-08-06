<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Middlewares;

use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Http\HttpRequest;

class NopMiddleware implements MiddlewareInterface
{
    public function handle(HttpRequest $request, callable $next): mixed
    {
        return $next($request);
    }
}
