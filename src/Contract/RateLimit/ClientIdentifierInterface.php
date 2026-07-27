<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\RateLimit;

use Vasoft\Joke\Http\HttpRequest;

/**
 * Контракт для определения идентификатора клиента из запроса.
 *
 * Реализации определяют, как именно идентифицировать клиента:
 * - По IP адресу (с поддержкой trusted proxies)
 * - По сессии / пользователю
 * - По API ключу
 * - Комбинированным методам
 */
interface ClientIdentifierInterface
{
    /**
     * Определяет уникальный идентификатор клиента из запроса.
     *
     * Возвращаемая строка используется как ключ в хранилище счётчиков.
     * Должна быть уникальной и детерминированной для одного клиента.
     *
     * @param HttpRequest $request HTTP запрос
     * @return string Идентификатор клиента (e.g. "192.168.1.1", "user:123", "api-key:abc...")
     */
    public function identify(HttpRequest $request): string;
}
