<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Core;

use Vasoft\Joke\Contract\Core\Routing\RouterInterface;
use Vasoft\Joke\Core\Exceptions\ParameterResolveException;

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
