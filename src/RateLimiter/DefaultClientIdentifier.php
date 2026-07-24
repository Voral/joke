<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter;

use Vasoft\Joke\Http\HttpRequest;

/**
 * Реализация идентификации по умолчанию.
 *
 * Использует IP-адрес клиента как идентификатор.
 */
class DefaultClientIdentifier implements ClientIdentifierInterface
{
    /**
     * {@inheritDoc}
     */
    public function identify(HttpRequest $request): string
    {
        // Приоритет: X-Forwarded-For → X-Real-IP → REMOTE_ADDR
        $forwardedFor = $request->server->getString('HTTP_X_FORWARDED_FOR', '');
        
        if ($forwardedFor !== '') {
            // X-Forwarded-For может содержать несколько IP чере�� запятую
            $ips = array_map('trim', explode(',', $forwardedFor));
            // Берем первый IP (исходный клиент)
            $ip = $ips[0];
            
            // Валидация IP
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        $realIp = $request->server->getString('HTTP_X_REAL_IP', '');
        
        if ($realIp !== '' && filter_var($realIp, FILTER_VALIDATE_IP)) {
            return $realIp;
        }

        return $request->server->getString('REMOTE_ADDR', 'unknown');
    }
}
