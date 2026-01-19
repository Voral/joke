<?php

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;

/**
 * Объект описывающий миддлвар в коллекции. Не пустое наименование является признаком единственного экземпляра
 */
class MiddlewareDto
{
    public array $groups = [];

    /**
     * @param MiddlewareInterface|string $middleware Миддлвар
     * @param string $name Наименование миддваров для одиночек
     * @param array<string> $groups Привязка миддлвра к набору групп. (Имеет значение в миддлварах привязанных к маршруту)
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