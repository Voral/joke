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
     * @param array<MiddlewareDto> $middlewares
     * @return $this
     */
    public function withMiddlewares(array $middlewares): static
    {
        $instance = clone $this;
        foreach ($middlewares as $middleware) {
            $instance->addMiddleware($middleware->middleware, $middleware->name, $middleware->groups);
        }
        return $instance;
    }

    /**
     * Возвращает развернутый список миддлваров для запуска
     * @param array<string,bool> $group Массив групп для фильтрации. Если передан пустой массив будут возвращены только
     *                             мидлвары с пустым списком групп, если не пустой, то с пересечением либо если у мидлавара
     *                              нет групп
     * @return array<MiddlewareInterface|string>
     */
    public function getArrayForRun(array $group = []): array
    {
        $filtered = empty($group)
            ? array_filter(
                $this->getMiddlewares(),
                static fn($middleware) => empty($middleware->groups)
            )
            : array_filter(
                $this->getMiddlewares(),
                static fn($middleware) => empty($middleware->groups)
                    || !empty(array_intersect_key($middleware->groups, $group))
            );
        $result = array_map(fn(MiddlewareDto $middleware) => $middleware->middleware, $filtered);
        return array_reverse($result);
    }
}