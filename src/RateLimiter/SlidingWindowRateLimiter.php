<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter;

use Vasoft\Joke\Contract\Storage\StorageInterface;
use Vasoft\Joke\Contract\Storage\RateLimiterInterface;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Реализация Rate Limiter на основе Sliding Window Log.
 *
 * Алгоритм:
 * - Хранит массив временных меток (timestamps) для каждого клиента
 * - При каждом запросе удаляет устаревшие метки (старше окна)
 * - Проверяет количество оставшихся меток
 * - Если лимит не превышен - добавляет новую метку
 *
 * Преимущества:
 * - Простота реализации на файловом хранилище
 * - Точное ограничение по времени
 * - Поддержка burst-поведения
 */
class SlidingWindowRateLimiter implements RateLimiterInterface
{
    /**
     * Префикс для ключей хранилища.
     */
    private const PREFIX = 'ratelimit:';

    /**
     * @var StorageInterface Хранилище для данных
     */
    private StorageInterface $storage;

    /**
     * @param StorageInterface $storage Хранилище данных
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function check(string $clientKey, int $limit, int $window): array
    {
        $key = self::PREFIX . $clientKey;
        
        $now = time();
        $windowStart = $now - $window;

        try {
            $result = $this->storage->cas($key, function (?string $value) use ($windowStart, $limit, $now) {
                // Инициализация пустого массива
                $timestamps = [];
                
                if ($value !== null) {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded) && isset($decoded['timestamps'])) {
                        $timestamps = $decoded['timestamps'];
                    }
                }

                // Фильтруем устаревшие метки
                $timestamps = array_filter(
                    $timestamps,
                    fn(int $timestamp) => $timestamp > $windowStart
                );

                // Переиндексируем массив
                $timestamps = array_values($timestamps);

                // Проверяем лимит
                $count = count($timestamps);
                
                if ($count >= $limit) {
                    // Лимит превышен - не добавляем новую метку
                    return $value;
                }

                // Добавляем новую метку
                $timestamps[] = $now;
                
                return json_encode([
                    'timestamps' => $timestamps,
                ], JSON_THROW_ON_ERROR);
            }, $window); // TTL = window (время жизни данных в хранилище)

            // Получаем текущее количество меток
            $decoded = json_decode($result['value'], true);
            $currentCount = is_array($decoded) && isset($decoded['timestamps']) 
                ? count($decoded['timestamps']) 
                : 0;
            
            // Если текущее количество >= лимита, значит запрос отклонен
            if ($currentCount >= $limit) {
                $oldestTimestamp = $this->getOldestTimestamp($key);
                $resetAt = $oldestTimestamp !== null ? $oldestTimestamp + $window : $now + $window;
                $retryAfter = max(1, $resetAt - $now);

                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'resetAt' => $resetAt,
                    'retryAfter' => $retryAfter,
                ];
            }

            // Оставшееся количество = лимит - текущее количество
            $remaining = max(0, $limit - $currentCount);
            
            return [
                'allowed' => true,
                'remaining' => $remaining,
                'resetAt' => $now + $window,
                'retryAfter' => null,
            ];
        } catch (\Throwable $e) {
            // В случае ошибки хранилища - разрешаем запрос (fail-open)
            return [
                'allowed' => true,
                'remaining' => $limit - 1,
                'resetAt' => $now + $window,
                'retryAfter' => null,
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(string $clientKey, int $limit, int $window): array
    {
        $key = self::PREFIX . $clientKey;
        $now = time();
        $windowStart = $now - $window;

        try {
            $value = $this->storage->get($key);
            
            $timestamps = [];
            if ($value !== null) {
                $decoded = json_decode($value, true);
                if (is_array($decoded) && isset($decoded['timestamps'])) {
                    $timestamps = $decoded['timestamps'];
                }
            }

            // Фильтруем устаревшие метки
            $timestamps = array_filter(
                $timestamps,
                fn(int $timestamp) => $timestamp > $windowStart
            );

            $currentCount = count($timestamps);
            $resetAt = $now + $window;

            return [
                'current' => $currentCount,
                'limit' => $limit,
                'window' => $window,
                'resetAt' => $resetAt,
            ];
        } catch (\Throwable $e) {
            return [
                'current' => 0,
                'limit' => $limit,
                'window' => $window,
                'resetAt' => $now + $window,
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function reset(string $clientKey): bool
    {
        $key = self::PREFIX . $clientKey;
        
        return $this->storage->delete($key);
    }

    /**
     * Получает oldest timestamp из хранилища.
     *
     * @param string $key Ключ записи
     *
     * @return int|null Первый timestamp или null
     */
    private function getOldestTimestamp(string $key): ?int
    {
        $value = $this->storage->get($key);
        
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded) || !isset($decoded['timestamps'])) {
            return null;
        }

        $timestamps = $decoded['timestamps'];
        
        if (empty($timestamps)) {
            return null;
        }

        return min($timestamps);
    }
}
