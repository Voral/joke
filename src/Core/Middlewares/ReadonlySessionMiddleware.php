<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;
use Vasoft\Joke\Core\Request\HttpRequest;

/**
 * Неблокирующая сессия. (данные считываются, сессия немедленно закрывается).
 *
 * Предназначен для сценариев, где:
 * - сессия нужна только для чтения (например, проверка авторизации)
 * - важна высокая параллельность (несколько AJAX-запросов от одного пользователя)
 */
class ReadonlySessionMiddleware implements MiddlewareInterface
{
    public function handle(HttpRequest $request, callable $next): mixed
    {
        if (!$request->session->isStarted()) {
            session_start();
        }
        $request->session->load();
        session_write_close();

        return $next();
    }
}
