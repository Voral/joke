<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Middleware;

use Random\RandomException;
use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Exceptions\JokeException;
use Vasoft\Joke\Http\Cookies\Exceptions\CookieException;
use Vasoft\Joke\Http\Response\Response;
use Vasoft\Joke\Http\Response\ResponseBuilder;
use Vasoft\Joke\Middleware\Config\CsrfConfig;
use Vasoft\Joke\Middleware\Config\Enums\CsrfTransportMode;
use Vasoft\Joke\Middleware\Exceptions\CsrfMismatchException;
use Vasoft\Joke\Http\HttpMethod;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Обеспечивает защиту от межсайтовой подделки запроса (CSRF):
 *
 * Генерирует токен, если его нет в сессии (csrf_token).
 * Для небезопасных методов (POST, PUT, DELETE и др.) проверяет наличие токена:
 * - в параметрах запроса: ?csrf_token=... или csrf_token=... (POST),
 * - в заголовке: X-Csrf-Token: ....
 * - в cookie: XSRF-TOKEN
 * При несоответствии выбрасывает CsrfMismatchException (HTTP 403).
 *
 * Токен генерируется автоматически. Вам нужно только передать его в форму или заголовок.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    public const string CSRF_TOKEN_NAME = 'csrf_token';
    public const string CSRF_TOKEN_HEADER = 'X-Csrf-Token';
    public const string CSRF_TOKEN_COOKIE = 'XSRF-TOKEN';
    private const array SAFE_METHODS = [HttpMethod::GET, HttpMethod::HEAD];

    public function __construct(
        private readonly ResponseBuilder $responseBuilder,
        private readonly CsrfConfig $config = new CsrfConfig(),
    ) {
        $config->freeze();
    }

    /**
     * @throws CsrfMismatchException
     * @throws RandomException
     * @throws JokeException
     */
    public function handle(HttpRequest $request, callable $next): Response
    {
        $token = $request->session->getString(self::CSRF_TOKEN_NAME, '');
        if ('' === $token) {
            $token = bin2hex(random_bytes(32));
            $request->session->set(self::CSRF_TOKEN_NAME, $token);
        }
        if (!in_array($request->method, self::SAFE_METHODS, true)) {
            $tokenFromRequest = trim(
                $request->get->getString(self::CSRF_TOKEN_NAME, '')
                    ?: $request->post->getString(self::CSRF_TOKEN_NAME, '')
                    ?: $request->headers->getString(self::CSRF_TOKEN_HEADER, '')
                        ?: $request->cookies->getString(self::CSRF_TOKEN_COOKIE, ''),
            );

            if ('' === $tokenFromRequest || !hash_equals($token, $tokenFromRequest)) {
                throw new CsrfMismatchException();
            }
        }

        return $this->injectToken($next(), $token);
    }

    /**
     * Гарантирует, что ответ является объектом Response, и добавляет заголовок с токеном.
     *
     * @param mixed  $response Ответ от предыдущего обработчика (строка, массив, объект Response и т.д.)
     * @param string $token    Актуальный CSRF-токен для внедрения
     *
     * @return Response Модифицированный объект ответа с заголовком X-Csrf-Token
     *
     * @throws CookieException
     */
    private function injectToken(mixed $response, string $token): Response
    {
        $preparedResponse = $this->responseBuilder->make($response);
        if (CsrfTransportMode::HEADER === $this->config->transportMode) {
            $preparedResponse->headers->set(self::CSRF_TOKEN_HEADER, $token);
        } else {
            $cookieConfig = $this->config->cookieConfig;
            $preparedResponse->cookies->add(
                self::CSRF_TOKEN_COOKIE,
                $token,
                $cookieConfig->lifetime,
                $cookieConfig->path,
                $cookieConfig->domain,
                $cookieConfig->secure,
                httpOnly: false,
                sameSite: $cookieConfig->sameSite,
            );
        }

        return $preparedResponse;
    }
}
