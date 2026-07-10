<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter\ClientIdentifier;

use Vasoft\Joke\Contract\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Идентификация клиента по API-ключу.
 *
 * Извлекает ключ из заголовка X-API-Key.
 * Если ключ отсутствует, использует IP-адрес как fallback.
 */
class ApiKeyClientIdentifier implements ClientIdentifierInterface
{
    /**
     * {@inheritDoc}
     */
    public function identify(HttpRequest $request): string
    {
        $apiKey = $request->headers->getString('X-API-Key', '');
        if ('' !== $apiKey) {
            return sprintf('apikey:%s', $apiKey);
        }

        return sprintf('ip:%s', $request->server->getStringOrDefault('REMOTE_ADDR', 'unknown'));
    }
}