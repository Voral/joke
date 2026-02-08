<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares\Exceptions;

class WrongMiddlewareException extends MiddlewareException
{
    public function __construct(string $middleware, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf("'Middleware %s must implements MiddlewareInterface", $middleware),
            $code,
            $previous,
        );
    }
}
