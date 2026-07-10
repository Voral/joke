<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter;

/**
 * Конфигурация правила ограничения частоты запросов.
 *
 * Определяет лимит и размер временного окна для конкретного правила.
 * Используется в SlidingWindowRateLimiter для вычисления допустимого количества запросов.
 */
final readonly class RuleConfig
{
    /**
     * @param int    $limit      Максимальное количество запросов в окне
     * @param int    $windowSize Размер временного окна в секундах
     * @param string $name       Имя правила
     */
    public function __construct(
        public int $limit,
        public int $windowSize,
        public string $name = 'default',
    ) {}
}