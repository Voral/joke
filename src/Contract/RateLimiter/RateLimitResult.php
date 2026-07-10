<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\RateLimiter;

/**
 * Результат проверки лимита частоты запросов.
 *
 * Содержит информацию о том, разрешён ли запрос,
 * сколько осталось запросов в текущем окне и когда окно сбросится.
 */
final readonly class RateLimitResult
{
    /**
     * @param bool   $allowed    Разрешён ли запрос
     * @param int    $remaining  Сколько запросов осталось в текущем окне
     * @param int    $limit      Максимальное количество разрешённых запросов
     * @param int    $resetTime  Unix-метка времени, когда окно сбросится
     * @param string $identifier Идентификатор клиента, использованный для проверки
     */
    public function __construct(
        public bool $allowed,
        public int $remaining,
        public int $limit,
        public int $resetTime,
        public string $identifier,
    ) {}
}