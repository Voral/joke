<?php

namespace Vasoft\Joke\Core\Routing\Exceptions;

use Vasoft\Joke\Core\Exceptions\JokeException;
use Vasoft\Joke\Core\Response\ResponseStatus;

class NotFoundException extends JokeException
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }

    public function getResponseStatus(): ResponseStatus
    {
        return ResponseStatus::NOT_FOUND;
    }
}