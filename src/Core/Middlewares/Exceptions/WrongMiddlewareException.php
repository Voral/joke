<?php

namespace Vasoft\Joke\Core\Middlewares\Exceptions;

use Throwable;

class WrongMiddlewareException extends MiddlewareException
{
public function __construct(string $middleware, int $code = 0, ?Throwable $previous = null)
{
    parent::__construct(
        sprintf("'Middleware %s must implements MiddlewareInterface", $middleware),
        $code,
        $previous
    );
}
}