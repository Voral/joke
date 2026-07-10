<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter;

use Vasoft\Joke\Contract\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\Contract\RateLimiter\RateLimitResult;
use Vasoft\Joke\Contract\RateLimiter\RateLimiterInterface;
use Vasoft\Joke\Contract\Storage\StorageInterface;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\RateLimiter\Exceptions\RateLimiterException;

/**
 * Реализация Rate Limiter на алгоритме Sliding Window (скользящее окно).
 *
 * Алгоритм:
 * 1. Текущее окно: floor(time / windowSize) * windowSize
 * 2. Предыдущее окно: текущее окно - windowSize
 * 3. Текущий счётчик: атомарный инкремент ключа текущего окна
 * 4. Предыдущий счётчик: чтение ключа предыдущего окна
 * 5. Вес: (time - начало_текущего_окна) / windowSize
 * 6. Оценка: предыдущий_счётчик * (1 - вес) + текущий_счётчик
 *
 * Преимущества:
 * - Требует только один атомарный инкремент (increment)
 * - Не требует read-modify-write циклов
 * - Сглаживает границы окон
 *
 * @see RateLimiterInterface
 */
class SlidingWindowRateLimiter implements RateLimiterInterface
{
    /**
     * @param StorageInterface          $storage    Хранилище для счётчиков
     * @param ClientIdentifierInterface $identifier Стратегия идентификации клиента
     * @param array<string, RuleConfig> $rules      Правила ограничения запросов
     */
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly ClientIdentifierInterface $identifier,
        private readonly array $rules = [],
    ) {}

    /**
     * {@inheritDoc}
     *
     * @throws RateLimiterException Если правило с указанным именем не найдено
     */
    public function check(HttpRequest $request, string $ruleName = 'default'): RateLimitResult
    {
        $rule = $this->rules[$ruleName] ?? throw new RateLimiterException(
            sprintf('Правило "%s" не найдено', $ruleName),
        );

        $clientId = $this->identifier->identify($request);
        $now = time();
        $windowSize = $rule->windowSize;

        // Текущее окно
        $currentWindow = (int) (floor($now / $windowSize) * $windowSize);
        $currentKey = $this->buildKey($clientId, $currentWindow);

        // Атомарный инкремент счётчика текущего окна
        $currentCount = $this->storage->increment($currentKey, $windowSize * 2);

        // Предыдущее окно (для расчёта скользящего среднего)
        $previousWindow = $currentWindow - $windowSize;
        $previousKey = $this->buildKey($clientId, $previousWindow);
        $previousCount = (int) ($this->storage->get($previousKey) ?? '0');

        // Вес: насколько мы углубились в текущее окно
        $weight = ($now - $currentWindow) / $windowSize;

        // Оценка количества запросов в скользящем окне
        $estimatedCount = (int) ($previousCount * (1 - $weight) + $currentCount);

        $allowed = $estimatedCount <= $rule->limit;
        $remaining = max(0, $rule->limit - $currentCount);
        $resetTime = $currentWindow + $windowSize;

        return new RateLimitResult(
            allowed: $allowed,
            remaining: $remaining,
            limit: $rule->limit,
            resetTime: $resetTime,
            identifier: $clientId,
        );
    }

    /**
     * Формирует ключ для хранения счётчика.
     *
     * @param string $clientId    Идентификатор клиента
     * @param int    $windowStart Unix-метка начала окна
     *
     * @return string Ключ для хранилища
     */
    private function buildKey(string $clientId, int $windowStart): string
    {
        return sprintf('ratelimit:%s:%d', $clientId, $windowStart);
    }
}