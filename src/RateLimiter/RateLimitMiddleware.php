<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter;

use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Contract\Storage\RateLimiterInterface;
use Vasoft\Joke\Http\Cookies\CookieConfig;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\JsonResponse;

/**
 * Middleware для Rate Limiting.
 *
 * Использует RateLimiter для проверки лимитов запросов.
 * Возвращает 429 Too Many Requests если лимит превышен.
 *
 * @example
 * // В роуте
 * ->middleware(RateLimitMiddleware::class)
 *
 * // С параметрами: limit,window
 * ->middleware(RateLimitMiddleware::class . ':100,60')
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * @var RateLimiterInterface Rate limiter
     */
    private RateLimiterInterface $rateLimiter;

    /**
     * @var ClientIdentifierInterface Идентификатор клиентов
     */
    private ClientIdentifierInterface $clientIdentifier;

    /**
     * @var int Максимальное количество запросов (по умолчанию 100)
     */
    private int $defaultLimit;

    /**
     * @var int Временное окно в секундах (по умолчанию 60)
     */
    private int $defaultWindow;

    /**
     * @var null|int Ограничение на количество запросов из параметра
     */
    private ?int $limit = null;

    /**
     * @var null|int Временное окно из параметра
     */
    private ?int $window = null;

    /**
     * @param RateLimiterInterface      $rateLimiter      Rate limiter
     * @param ClientIdentifierInterface $clientIdentifier Идентификатор клиентов
     */
    public function __construct(
        RateLimiterInterface $rateLimiter,
        ClientIdentifierInterface $clientIdentifier,
        //        int $defaultLimit = 100,
        //        int $defaultWindow = 60
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->clientIdentifier = $clientIdentifier;
        $this->defaultLimit = 3; // $defaultLimit;
        $this->defaultWindow = 60; // $defaultWindow;
    }

    /**
     * Устанавливает параметры из строки.
     *
     * @param string $params Параметры в формате "limit,window"
     */
    public function setParams(string $params): self
    {
        $parts = array_map('trim', explode(',', $params));

        if (count($parts) >= 1 && '' !== $parts[0]) {
            $this->limit = (int) $parts[0];
        }

        if (count($parts) >= 2 && '' !== $parts[1]) {
            $this->window = (int) $parts[1];
        }

        return $this;
    }

    public function handle(HttpRequest $request, callable $next): mixed
    {
        $limit = $this->limit ?? $this->defaultLimit;
        $window = $this->window ?? $this->defaultWindow;

        $clientKey = $this->clientIdentifier->identify($request);

        $result = $this->rateLimiter->check($clientKey, $limit, $window);

        // Добавляем заголовки rate limiting в ответ
        $response = $next($request);


        $response->headers
            ->set('X-RateLimit-Limit', (string) $limit)
            ->set('X-RateLimit-Remaining', (string) $result['remaining'])
            ->set('X-RateLimit-Reset', (string) $result['resetAt']);

        if (!$result['allowed']) {
            $response = new JsonResponse(new CookieConfig());
            $response->setBody([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $result['retryAfter'],
            ]);
            $response->headers
                ->set('Retry-After', (string) $result['retryAfter']);


            return $response;
        }

        return $response;
    }

    /**
     * Создает middleware из параметров.
     */
    public static function fromParams(
        RateLimiterInterface $rateLimiter,
        ClientIdentifierInterface $clientIdentifier,
        string $params = '',
    ): self {
        $middleware = new self(
            $rateLimiter,
            $clientIdentifier,
        );

        if ('' !== $params) {
            $middleware->setParams($params);
        }

        return $middleware;
    }
}
