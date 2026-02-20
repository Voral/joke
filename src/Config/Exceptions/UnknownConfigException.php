<?php

declare(strict_types=1);

namespace Vasoft\Joke\Config\Exceptions;

class UnknownConfigException extends ConfigException
{
    public function __construct(string $configClass, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct('Unknown config class: ' . $configClass, $code, $previous);
    }
}
