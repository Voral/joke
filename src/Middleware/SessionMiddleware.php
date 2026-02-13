<?php

declare(strict_types=1);

namespace Vasoft\Joke\Middleware;

use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Блокирующая сессия (сессия остаётся открытой на всё время обработки запроса).
 *
 * Запускает сессию, загружает данные и сохраняет их после выполнения цепочки middleware и обработчика. Это поведение по
 * умолчанию для большинства веб-приложений.
 */
class SessionMiddleware implements MiddlewareInterface
{
    public function handle(HttpRequest $request, callable $next): mixed
    {
        if (!$request->session->isStarted()) {
            session_start();
        }
        $request->session->load();
        $result = $next($request);
        $request->session->save();

        return $result;
    }
}
