<?php

namespace Vasoft\Joke\Core\Response;

enum ResponseStatus: int
{
    case OK = 200;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case INTERNAL_SERVER_ERROR = 500;

    public function http(): string
    {
        return match ($this) {
            self::OK => 'OK',
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
        };
    }
}
