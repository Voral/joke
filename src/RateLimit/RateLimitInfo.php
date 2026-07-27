<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimit;

/**
 * Информация о статусе rate limiting'а для клиента.
 *
 * Возвращается методом check() и может быть включена в HTTP заголовки ответа
 * (e.g. X-RateLimit-Remaining, X-RateLimit-Limit, Retry-After).
 */
final readonly class RateLimitInfo
{
    public function __construct(
        public bool $isBlocked,
        public int $remaining,
        public int $limit,
        public int $resetAfter,
    ) {}

    /**
     * Форматирует информацию для HTTP заголовков.
     *
     * @return array<string, string|int>
     */
    public function toHeaders(): array
    {
        return [
            'X-RateLimit-Limit' => $this->limit,
            'X-RateLimit-Remaining' => $this->remaining,
            'X-RateLimit-Reset' => time() + $this->resetAfter,
        ];
    }
}
