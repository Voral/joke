<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter\ClientIdentifier;

use Vasoft\Joke\Contract\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Идентификация клиента по ID пользователя (для авторизованных запросов).
 *
 * Если пользователь авторизован (user_id установлен в props запроса),
 * используется его ID. Иначе — IP-адрес как fallback.
 */
class UserIdClientIdentifier implements ClientIdentifierInterface
{
    /**
     * {@inheritDoc}
     */
    public function identify(HttpRequest $request): string
    {
        $userId = $request->props->getString('user_id', '');
        if ('' !== $userId) {
            return sprintf('user:%s', $userId);
        }

        return sprintf('ip:%s', $request->server->getStringOrDefault('REMOTE_ADDR', 'unknown'));
    }
}