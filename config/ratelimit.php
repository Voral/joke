<?php

declare(strict_types=1);

use Vasoft\Joke\Config\RateLimitConfig;

/**
 * Конфигурация Rate Limiter.
 *
 * Формат: Eager (загружается сразу при старте приложения)
 */
return (new RateLimitConfig())
    ->setDefaultLimit(100)           // 100 запросов в минуту
    ->setDefaultWindow(60)           // 60 секунд
    ->setDriver('file')              // file|array|redis
    ->setStoragePath(storage_path('ratelimit'))
    ->setClientIdentifierClass(\Vasoft\Joke\RateLimiter\DefaultClientIdentifier::class)
    ->freeze();
