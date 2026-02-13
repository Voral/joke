<?php

declare(strict_types=1);

namespace Vasoft\Joke\Session\Exceptions;

use Vasoft\Joke\Exceptions\JokeException;

class SessionException extends JokeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            '' === $message ? 'Readonly session mode. Can\'t write' : $message,
            $code,
            $previous,
        );
    }
}
