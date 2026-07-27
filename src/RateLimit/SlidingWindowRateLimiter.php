<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimit;

use Vasoft\Joke\Contract\RateLimit\ClientIdentifierInterface;
use Vasoft\Joke\Contract\RateLimit\StorageInterface;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Rate Limiter на основе скользящего окна (Sliding Window).
 *
 * Алгоритм:
 * - Счётчик инкрементируется при каждом запросе
 * - Запрос блокируется, если счётчик превышает лимит
 * - Старые запросы удаляются по TTL автоматически
 * - Очень простой и надежный алгоритм для микро-фреймворка
 */
final class SlidingWindowRateLimiter
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly ClientIdentifierInterface $clientIdentifier,
        private readonly int $limit,
        private readonly int $window,
    ) {}

    /**
     * Проверяет, разрешен ли запрос от клиента.
     *
     * @param HttpRequest $request HTTP запрос
     * @return RateLimitInfo Информация об оставшихся попытках и сброса
     */
    public function check(HttpRequest $request): RateLimitInfo
    {
        $clientId = $this->clientIdentifier->identify($request);
        $key = "ratelimit:{$clientId}";

        $counter = $this->storage->increment($key, $this->window);
        $remaining = max(0, $this->limit - $counter);
        $isBlocked = $counter > $this->limit;

        return new RateLimitInfo(
            isBlocked: $isBlocked,
            remaining: $remaining,
            limit: $this->limit,
            resetAfter: $this->window,
        );
    }
}
