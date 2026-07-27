<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimit;

use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Contract\RateLimit\ClientIdentifierInterface;
use Vasoft\Joke\Contract\RateLimit\StorageInterface;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\JsonResponse;
use Vasoft\Joke\Storage\Exceptions\StorageException;

/**
 * Middleware для rate limiting на основе скользящего окна.
 *
 * Блокирует запросы, превышающие лимит, с HTTP ответом 429 Too Many Requests.
 * Добавляет HTTP заголовки X-RateLimit-* для информирования клиента.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly ClientIdentifierInterface $clientIdentifier,
        private readonly RateLimitConfig $config,
        private readonly int $limit = 100,
        private readonly int $window = 3600,
    ) {}

    public function handle(HttpRequest $request, callable $next): mixed
    {
        if (!$this->config->enabled) {
            return $next($request);
        }

        try {
            $clientId = $this->clientIdentifier->identify($request);
            $key = "ratelimit:{$clientId}";

            $counter = $this->storage->increment($key, $this->window);
            $remaining = max(0, $this->limit - $counter);
            $isBlocked = $counter > $this->limit;

            $resetTime = time() + $this->window;

            if ($this->config->withHeaders) {
                $request->props->reset([
                    'X-RateLimit-Limit' => $this->limit,
                    'X-RateLimit-Remaining' => $remaining,
                    'X-RateLimit-Reset' => $resetTime,
                ]);
            }

            if ($isBlocked) {
                return new JsonResponse([
                    'error' => 'Too Many Requests',
                    'message' => "Rate limit exceeded: {$this->limit} requests per {$this->window} seconds",
                    'retry_after' => $this->window,
                ], 429, [
                    'X-RateLimit-Limit' => (string)$this->limit,
                    'X-RateLimit-Remaining' => (string)$remaining,
                    'X-RateLimit-Reset' => (string)$resetTime,
                    'Retry-After' => (string)$this->window,
                ]);
            }

            $response = $next($request);

            if ($this->config->withHeaders && isset($request->props)) {
                foreach ($request->props->all() as $key => $value) {
                    if (str_starts_with($key, 'X-RateLimit-') || $key === 'Retry-After') {
                        // Добавить заголовок если возможно
                    }
                }
            }

            return $response;
        } catch (StorageException) {
            return $next($request);
        }
    }
}
