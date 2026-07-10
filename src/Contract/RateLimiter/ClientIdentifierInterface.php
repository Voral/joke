<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\RateLimiter;

use Vasoft\Joke\Http\HttpRequest;

/**
 * Интерфейс идентификатора клиента для Rate Limiter.
 *
 * Определяет стратегию извлечения уникального идентификатора клиента из запроса.
 * Реализации могут использовать IP-адрес, ID пользователя, API-ключ и т.д.
 *
 * Примеры:
 * - IpClientIdentifier: $request->server->getStringOrDefault('REMOTE_ADDR')
 * - UserIdClientIdentifier: $request->props->getString('user_id')
 * - ApiKeyClientIdentifier: $request->headers->getString('X-API-Key')
 */
interface ClientIdentifierInterface
{
    /**
     * Извлекает уникальный идентификатор клиента из запроса.
     *
     * @param HttpRequest $request Входящий HTTP-запрос
     *
     * @return string Уникальный идентификатор для ограничения частоты запросов
     */
    public function identify(HttpRequest $request): string;
}