<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Container;

use Vasoft\Joke\Contract\Routing\RouterInterface;
use Vasoft\Joke\Container\Exceptions\ParameterResolveException;

/**
 * DI контейнер приложения
 * Обязательно содержит маршрутизатор
 */
interface ApplicationContainerInterface extends DiContainerInterface
{
    /**
     * Возвращает маршрутизатор
     *
     * @throws ParameterResolveException
     */
    public function getRouter(): RouterInterface;

    /**
     * Задает роутер
     *
     * @param callable|object|string $router Определение сервиса:
     *                                       - строка с именем класса
     *                                       - callable (фабрика)
     *                                       - готовый объект
     *
     * @return $this
     */
    public function setRouter(callable|object|string $router): static;
}
