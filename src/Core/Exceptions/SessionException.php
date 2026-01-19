<?php

namespace Vasoft\Joke\Core\Exceptions;

use Throwable;

class SessionException extends JokeException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            $message === '' ? 'Readonly session mode. Can\'t write' : $message,
            $code,
            $previous
        );
    }
}