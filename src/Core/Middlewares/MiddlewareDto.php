<?php

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;

/**
 * Объект описывающий миддлвар в коллекции. Не пустое наименование является признаком единственного экземпляра
 */
class MiddlewareDto
{
    /**
     * @param MiddlewareInterface|string $middleware Миддлвар
     * @param string $name Наименование миддваров для одиночек
     */
    public function __construct(
        public MiddlewareInterface|string $middleware,
        public readonly string $name = '',
    ) {
    }
}