<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimit\Exceptions;

use Vasoft\Joke\Exceptions\JokeException;

/**
 * Исключение, выбрасываемое при ошибках в работе Rate Limiter.
 *
 * Например, при запросе несуществующего правила или сбое хранилища.
 */
class RateLimitException extends JokeException {}
