<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter;

use Vasoft\Joke\Http\HttpRequest;

/**
 * Интерфейс для идентификации клиентов.
 */
interface ClientIdentifierInterface
{
    /**
     * Идентифицирует клиента на основе HTTP-запроса.
     *
     * @param HttpRequest $request Входящий HTTP-запрос
     *
     * @return string Уникальный идентификатор клиента
     */
    public function identify(HttpRequest $request): string;
}
