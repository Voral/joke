<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\RateLimiter;

use Vasoft\Joke\Http\HttpRequest;

/**
 * Интерфейс ограничителя частоты запросов (Rate Limiter).
 *
 * Определяет контракт для проверки лимитов запросов.
 * Реализации должны быть потокобезопасными и эффективными.
 */
interface RateLimiterInterface
{
    /**
     * Проверяет, разрешён ли запрос.
     *
     * @param HttpRequest $request  Входящий HTTP-запрос
     * @param string      $ruleName Имя правила лимита (например, 'api', 'login')
     *                              Позволяет применять разные лимиты к разным эндпоинтам
     *
     * @return RateLimitResult Результат проверки лимита
     */
    public function check(HttpRequest $request, string $ruleName = 'default'): RateLimitResult;
}