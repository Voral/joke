<?php

namespace Vasoft\Joke\Core\Exceptions\Http;

use Throwable;
use Vasoft\Joke\Core\Exceptions\JokeException;
use Vasoft\Joke\Core\Response\ResponseStatus;

class CsrfMismatchException extends JokeException
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('CSRF token mismatch', $code, $previous);
    }

    public function getResponseStatus(): ResponseStatus
    {
        return ResponseStatus::FORBIDDEN;
    }
}