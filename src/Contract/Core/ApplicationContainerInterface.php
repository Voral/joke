<?php

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
     * @return RouterInterface
     * @throws ParameterResolveException
     */
    public function getRouter(): RouterInterface;

    /**
     * Задает роутер
     * @param callable|string|object $router Определение сервиса:
     *        - строка с именем класса
     *        - callable (фабрика)
     *        - готовый объект
     * @return $this
     */
    public function setRouter(callable|string|object $router): static;
}