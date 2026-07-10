<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter\ClientIdentifier;

use Vasoft\Joke\Contract\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Идентификация клиента по IP-адресу.
 *
 * Простейшая стратегия: использует REMOTE_ADDR из серверных переменных.
 * Подходит для базовых сценариев, но не различает пользователей за NAT.
 */
class IpClientIdentifier implements ClientIdentifierInterface
{
    /**
     * {@inheritDoc}
     */
    public function identify(HttpRequest $request): string
    {
        return sprintf('ip:%s', $request->server->getStringOrDefault('REMOTE_ADDR', 'unknown'));
    }
}