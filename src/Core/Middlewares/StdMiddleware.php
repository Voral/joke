<?php

namespace Vasoft\Joke\Core\Middlewares;

/**
 * Наименования стандартных миддлваров для обеспечения единственного экземпляра
 */
enum StdMiddleware: string
{
    case SESSION = 'session';
    case EXCEPTION = 'exception';
}