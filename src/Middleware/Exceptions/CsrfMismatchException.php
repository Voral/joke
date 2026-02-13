<?php

declare(strict_types=1);

namespace Vasoft\Joke\Middleware\Exceptions;

use Vasoft\Joke\Http\Response\ResponseStatus;

class CsrfMismatchException extends MiddlewareException
{
    public function __construct(int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct('CSRF token mismatch', $code, $previous);
    }

    public function getResponseStatus(): ResponseStatus
    {
        return ResponseStatus::FORBIDDEN;
    }
}
