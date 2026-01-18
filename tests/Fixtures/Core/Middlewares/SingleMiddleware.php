<?php

namespace Vasoft\Joke\Tests\Fixtures\Core\Middlewares;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;
use Vasoft\Joke\Core\Request\HttpRequest;

class SingleMiddleware implements MiddlewareInterface
{
    public int $index = 0;

    public function __construct() { }

    public function handle(HttpRequest $request, callable $next): string
    {
        $response = sprintf('Middleware %d begin#', $this->index);
        $response .= $next();
        $response .= sprintf('#Middleware %d end', $this->index);
        return $response;
    }
}