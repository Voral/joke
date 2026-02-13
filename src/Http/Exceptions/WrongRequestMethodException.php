<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Exceptions;

use Vasoft\Joke\Exceptions\JokeException;
use Vasoft\Joke\Http\Response\ResponseStatus;

class WrongRequestMethodException extends JokeException
{
    public function __construct(string $method, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Wrong request method: %s', $method),
            $code,
            $previous,
        );
    }

    public function getResponseStatus(): ResponseStatus
    {
        return ResponseStatus::METHOD_NOT_ALLOWED;
    }
}
