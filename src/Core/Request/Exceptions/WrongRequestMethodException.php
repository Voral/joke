<?php

namespace Vasoft\Joke\Core\Request\Exceptions;

use Vasoft\Joke\Core\Exceptions\JokeException;
use Vasoft\Joke\Core\Response\ResponseStatus;

class WrongRequestMethodException extends JokeException
{
    public function __construct(string $method, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Wrong request method: %s', $method),
            $code,
            $previous
        );
    }

    public function getResponseStatus(): ResponseStatus
    {
        return ResponseStatus::METHOD_NOT_ALLOWED;
    }
}