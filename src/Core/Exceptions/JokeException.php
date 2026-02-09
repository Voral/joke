<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Exceptions;

use Vasoft\Joke\Core\Response\ResponseStatus;

class JokeException extends \Exception
{
    public function getResponseStatus(): ResponseStatus
    {
        return ResponseStatus::INTERNAL_SERVER_ERROR;
    }
}
