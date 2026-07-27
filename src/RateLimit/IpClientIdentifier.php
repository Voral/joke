<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimit;

use Vasoft\Joke\Contract\RateLimit\ClientIdentifierInterface;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Определяет идентификатор клиента по IP адресу.
 *
 * Поддерживает trusted proxies:
 * - Если REMOTE_ADDR в списке доверенных, берём первый IP из X-Forwarded-For
 * - Иначе используем только REMOTE_ADDR
 *
 * Это предотвращает спуфинг X-Forwarded-For от непроверенных источников.
 */
final class IpClientIdentifier implements ClientIdentifierInterface
{
    /**
     * @param list<string> $trustedProxies IP адреса доверенных прокси
     */
    public function __construct(
        private readonly array $trustedProxies = [],
    ) {}

    public function identify(HttpRequest $request): string
    {
        $remoteAddr = $request->server->getStringOrDefault('REMOTE_ADDR', '');

        if (!$remoteAddr) {
            return 'unknown';
        }

        // Если REMOTE_ADDR не в списке доверенных, используем его как есть
        if (!in_array($remoteAddr, $this->trustedProxies, true)) {
            return $remoteAddr;
        }

        // REMOTE_ADDR — доверенный прокси, смотрим X-Forwarded-For
        $forwarded = $request->headers->getString('X-Forwarded-For', '');
        if (!$forwarded) {
            return $remoteAddr;
        }

        // Берём первый IP из списка (ближайший к клиенту)
        $ips = array_map('trim', explode(',', $forwarded));
        $clientIp = $ips[0] ?? '';

        return $clientIp ?: $remoteAddr;
    }
}
