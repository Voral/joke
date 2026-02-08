<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;
use Vasoft\Joke\Core\Exceptions\Http\CsrfMismatchException;
use Vasoft\Joke\Core\Request\HttpMethod;
use Vasoft\Joke\Core\Request\HttpRequest;

/**
 * Обеспечивает защиту от межсайтовой подделки запроса (CSRF):
 *
 * Генерирует токен, если его нет в сессии (csrf_token).
 * Для небезопасных методов (POST, PUT, DELETE и др.) проверяет наличие токена:
 * - в параметрах запроса: ?csrf_token=... или csrf_token=... (POST),
 * - в заголовке: X-Csrf-Token: ....
 * При несоответствии выбрасывает CsrfMismatchException (HTTP 403).
 *
 * Токен генерируется автоматически. Вам нужно только передать его в форму или заголовок.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    public const string CSRF_TOKEN_NAME = 'csrf_token';
    public const string CSRF_TOKEN_HEADER = 'X-Csrf-Token';

    private const array SAFE_METHODS = [HttpMethod::GET, HttpMethod::HEAD];

    public function handle(HttpRequest $request, callable $next): mixed
    {
        $token = $request->session->get(self::CSRF_TOKEN_NAME);
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $request->session->set(self::CSRF_TOKEN_NAME, $token);
        }
        if (in_array($request->method, self::SAFE_METHODS, true)) {
            return $next();
        }

        $tokenFromRequest = trim(
            $request->get->get(self::CSRF_TOKEN_NAME)
            ?? $request->post->get(self::CSRF_TOKEN_NAME)
            ?? $request->headers->get(self::CSRF_TOKEN_HEADER)
            ?? '',
        );

        if (!hash_equals($token, $tokenFromRequest)) {
            throw new CsrfMismatchException();
        }

        return $next();
    }
}
