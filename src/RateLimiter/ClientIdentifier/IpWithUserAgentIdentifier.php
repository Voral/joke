<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter\ClientIdentifier;

use Vasoft\Joke\Contract\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Идентификация клиента по IP-адресу и User-Agent.
 *
 * Комбинирует IP и User-Agent для лучшего различения клиентов за NAT.
 * User-Agent хешируется для сокращения длины идентификатора.
 */
class IpWithUserAgentIdentifier implements ClientIdentifierInterface
{
    /**
     * {@inheritDoc}
     */
    public function identify(HttpRequest $request): string
    {
        $ip = $request->server->getStringOrDefault('REMOTE_ADDR', 'unknown');
        $ua = $request->headers->getString('User-Agent', '');

        return sprintf('ip:%s:ua:%s', $ip, md5($ua));
    }
}