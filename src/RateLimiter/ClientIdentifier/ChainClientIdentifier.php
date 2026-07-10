<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter\ClientIdentifier;

use Vasoft\Joke\Contract\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\Http\HttpRequest;

/**
 * Цепочка идентификаторов клиента.
 *
 * Позволяет комбинировать несколько стратегий идентификации.
 * Каждая стратегия вызывается по порядку, пока одна из них
 * не вернёт идентификатор, отличный от fallback (ip:unknown).
 *
 * Пример использования:
 * ```php
 * $identifier = new ChainClientIdentifier([
 *     new UserIdClientIdentifier(),
 *     new ApiKeyClientIdentifier(),
 *     new IpClientIdentifier(),
 * ]);
 * ```
 */
class ChainClientIdentifier implements ClientIdentifierInterface
{
    /**
     * @param ClientIdentifierInterface[] $identifiers Список стратегий идентификации в порядке приоритета
     */
    public function __construct(
        private readonly array $identifiers,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function identify(HttpRequest $request): string
    {
        foreach ($this->identifiers as $identifier) {
            $result = $identifier->identify($request);
            if (!str_starts_with($result, 'ip:unknown')) {
                return $result;
            }
        }

        return 'ip:unknown';
    }
}