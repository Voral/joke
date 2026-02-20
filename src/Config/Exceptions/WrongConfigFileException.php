<?php

declare(strict_types=1);

namespace Vasoft\Joke\Config\Exceptions;

class WrongConfigFileException extends ConfigException
{
    public function __construct(string $file, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            'Config file ' . $file . ' must return a instance of Vasoft\Joke\Config\AbstractConfig',
            $code,
            $previous,
        );
    }
}
