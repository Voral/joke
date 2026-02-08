<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Routing\Exceptions;

use Vasoft\Joke\Core\Exceptions\ParameterResolveException;

class AutowiredException extends ParameterResolveException
{
    public function __construct(string $paramName, string $type, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Failed to autowire parameter "$%s": expected type "%s" cannot be resolved or is incompatible with the provided value.',
                $paramName,
                $type,
            ),
            $code,
            $previous,
        );
    }
}
