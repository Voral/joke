<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Csrf;

/**
 * Способ доставки CSRF токена.
 */
enum CsrfTransportMode: int
{
    /** В куках */
    case COOKIE = 1;
    /** В заголовке */
    case HEADER = 2;
}
