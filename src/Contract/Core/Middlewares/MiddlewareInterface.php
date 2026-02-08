<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Core\Middlewares;

use Vasoft\Joke\Core\Request\HttpRequest;

/**
 * Все Middleware в системе должны реализовывать этот интерфейс
 */
interface MiddlewareInterface
{
    /**
     * @param HttpRequest $request входящий HTTP-запрос
     * @param callable    $next    callable, представляющий следующее звено цепочки
     */
    public function handle(HttpRequest $request, callable $next): mixed;
}
