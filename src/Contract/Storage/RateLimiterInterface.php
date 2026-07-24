<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Storage;

/**
 * Интерфейс Rate Limiter.
 */
interface RateLimiterInterface
{
    /**
     * Проверяет, можно ли выполнить запрос для клиента.
     *
     * @param string $clientKey   Уникальный идентификатор клиента
     * @param int    $limit       Максимальное количество запросов
     * @param int    $window      Временное окно в секундах
     *
     * @return array{allowed: bool, remaining: int, resetAt: int, retryAfter: ?int} Результат проверки
     */
    public function check(string $clientKey, int $limit, int $window): array;

    /**
     * Получает статистику по клиенту.
     *
     * @param string $clientKey Уникальный идентификатор клиента
     * @param int    $limit     Максимальное количество запросов
     * @param int    $window    Временное окно в секундах
     *
     * @return array{current: int, limit: int, window: int, resetAt: int} Статистика
     */
    public function getStats(string $clientKey, int $limit, int $window): array;

    /**
     * Сбрасывает лимит для клиента.
     *
     * @param string $clientKey Уникальный идентификатор клиента
     *
     * @return bool true при успешном сбросе
     */
    public function reset(string $clientKey): bool;
}
