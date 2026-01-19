<?php

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;

/**
 * Коллекция миддваров.
 *
 * Обеспечивает хранение набора миддлваров и единственность экземпляров для именованных
 */
class MiddlewareCollection
{
    /**
     * @var array<MiddlewareDto>
     */
    private array $middlewares = [];

    /**
     * Добавляет миддлвар в коллекцию
     * Если мидллвар именованный производится поиск, и, если найден, производится замена миддлвара и групп в той же позиции где
     * и был найден
     * @param MiddlewareInterface|string $middleware
     * @param string $name
     * @param array<string> $groups Привязка миддлвра к набору групп. (Имеет значение в миддлварах привязанных к маршруту)
     * @return $this
     */
    public function addMiddleware(MiddlewareInterface|string $middleware, string $name = '', array $groups = []): static
    {
        if ($name === '') {
            $this->middlewares[] = new MiddlewareDto($middleware, groups: $groups);
            return $this;
        }
        foreach ($this->middlewares as $exits) {
            if ($name === $exits->name) {
                $exits->middleware = $middleware;
                $exits->groups = $groups;
                return $this;
            }
        }
        $this->middlewares[] = new MiddlewareDto($middleware, $name, $groups);
        return $this;
    }

    /**
     * @return MiddlewareDto[] Список миддлваров
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Клонирует коллекцию с дополнительными наборами, для именованных обеспечивается единственность экземпляра и
     * позиция в списке как при первом добавлении
     * @param array $middlewares
     * @return $this
     */
    public function withMiddlewares(array $middlewares): static
    {
        $instance = clone $this;
        foreach ($middlewares as $middleware) {
            $instance->addMiddleware($middleware->middleware, $middleware->name);
        }
        return $instance;
    }

    /**
     * Возвращает развернутый список миддлваров для запуска
     * @return array
     */
    public function getListForRun(): array
    {
        $result = array_map(fn(MiddlewareDto $middleware) => $middleware->middleware, $this->getMiddlewares());
        return array_reverse($result);
    }
}