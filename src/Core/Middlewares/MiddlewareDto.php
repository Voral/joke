<?php

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;

/**
 * Объект описывающий middleware в коллекции. Не пустое наименование является признаком единственного экземпляра
 */
class MiddlewareDto
{
    public array $groups = [];

    /**
     * @param MiddlewareInterface|string $middleware middleware
     * @param string $name Наименование middleware для одиночек
     * @param array<string> $groups Привязка middleware к набору групп. (Имеет значение в middleware привязанных к маршруту)
     */
    public function __construct(
        public MiddlewareInterface|string $middleware,
        public readonly string $name = '',
        array $groups = [],
    ) {
        foreach ($groups as $group) {
            $this->groups[$group] = true;
        }
    }
}