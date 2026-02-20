<?php

declare(strict_types=1);

namespace Vasoft\Joke\Config\Exceptions;

class WrongConfigException extends ConfigException
{
    public function __construct(string $name, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            'Wrong config for ' . $name . ' must return a instance of Vasoft\Joke\Config\AbstractConfig',
            $code,
            $previous,
        );
    }
}
