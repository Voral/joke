<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter\Middleware;

use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Contract\RateLimiter\RateLimitResult;
use Vasoft\Joke\Contract\RateLimiter\RateLimiterInterface;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\Response;
use Vasoft\Joke\Http\Response\ResponseBuilder;
use Vasoft\Joke\Http\Response\ResponseStatus;
use Vasoft\Joke\RateLimiter\RateLimiterConfig;

/**
 * Middleware для ограничения частоты запросов (Rate Limiting).
 *
 * Проверяет каждый входящий запрос через RateLimiterInterface.
 * Если лимит превышен, возвращает HTTP 429 Too Many Requests.
 * В успешном случае добавляет заголовки X-RateLimit-* к ответу.
 *
 * Правило выбирается на основе пути запроса через конфигурацию.
 *
 * @see RateLimiterInterface
 * @see RateLimiterConfig
 */
class RateLimiterMiddleware implements MiddlewareInterface
{
    /**
     * @param RateLimiterInterface $rateLimiter     Ограничитель запросов
     * @param ResponseBuilder      $responseBuilder Билдер ответов
     * @param RateLimiterConfig    $config          Конфигурация rate limiter
     */
    public function __construct(
        private readonly RateLimiterInterface $rateLimiter,
        private readonly ResponseBuilder $responseBuilder,
        private readonly RateLimiterConfig $config,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function handle(HttpRequest $request, callable $next): Response
    {
        $ruleName = $this->resolveRule($request);

        $result = $this->rateLimiter->check($request, $ruleName);

        if (!$result->allowed) {
            return $this->buildRateLimitResponse($result);
        }

        $response = $next($request);
        $preparedResponse = $this->responseBuilder->make($response);

        $preparedResponse->headers
            ->set('X-RateLimit-Limit', (string) $result->limit)
            ->set('X-RateLimit-Remaining', (string) $result->remaining)
            ->set('X-RateLimit-Reset', (string) $result->resetTime);

        return $preparedResponse;
    }

    /**
     * Определяет имя правила для запроса на основе пути.
     *
     * Сопоставляет путь запроса с паттернами из конфигурации.
     * Если ни один паттерн не подошёл, возвращает 'default'.
     *
     * @param HttpRequest $request Входящий HTTP-запрос
     *
     * @return string Имя правила
     */
    private function resolveRule(HttpRequest $request): string
    {
        $path = $request->getPath();

        foreach ($this->config->getRules() as $name => $rule) {
            // Проверяем, начинается ли путь с имени правила
            if ('' !== $name && 'default' !== $name && str_starts_with($path, '/' . $name)) {
                return $name;
            }
        }

        return 'default';
    }

    /**
     * Формирует ответ с кодом 429 Too Many Requests.
     *
     * @param RateLimitResult $result Результат проверки лимита
     *
     * @return Response Ответ с заголовками ограничения
     */
    private function buildRateLimitResponse(RateLimitResult $result): Response
    {
        $response = $this->responseBuilder->makeDefault();
        $response->setStatus(ResponseStatus::TOO_MANY_REQUESTS);
        $response->headers
            ->set('Retry-After', (string) ($result->resetTime - time()))
            ->set('X-RateLimit-Limit', (string) $result->limit)
            ->set('X-RateLimit-Remaining', '0')
            ->set('X-RateLimit-Reset', (string) $result->resetTime);

        return $response;
    }
}