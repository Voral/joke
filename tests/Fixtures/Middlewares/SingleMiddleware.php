<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Middlewares;

use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Http\HttpRequest;

class SingleMiddleware implements MiddlewareInterface
{
    public int $index = 0;

    public function __construct() {}

    public function handle(HttpRequest $request, callable $next): string
    {
        $response = sprintf('Middleware %d begin#', $this->index);
        $response .= $next();
        $response .= sprintf('#Middleware %d end', $this->index);

        return $response;
    }
}
