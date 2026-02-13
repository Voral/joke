<?php

declare(strict_types=1);

namespace Vasoft\Joke\Exceptions;

use Vasoft\Joke\Http\Response\ResponseStatus;

class JokeException extends \Exception
{
    public function getResponseStatus(): ResponseStatus
    {
        return ResponseStatus::INTERNAL_SERVER_ERROR;
    }
}
