<?php

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Contract\Core\ApplicationContainerInterface;
use Vasoft\Joke\Contract\Core\Routing\RouterInterface;
use Vasoft\Joke\Core\Routing\Router;

/**
 * Контейнер внедрения зависимостей (DI Container) приложения.
 *
 * Управляет жизненным циклом сервисов, поддерживает синглтоны и прототипы,
 * автоматически разрешает зависимости через рефлексию и интегрируется
 * с системой маршрутизации фреймворка.
 */
class ServiceContainer extends BaseContainer implements ApplicationContainerInterface
{
    /**
     * Инициализирует стандартные сервисы по умолчанию.
     *
     * Регистрирует реализации ResolverInterface и RouterInterface.
     */
    protected function initDefault(): void
    {
        parent::initDefault();
        $this->setRouter(Router::class);
    }

    /**
     * @inheritDoc
     */
    public function getRouter(): RouterInterface
    {
        return $this->get(RouterInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function setRouter(callable|object|string $router): static
    {
        $this->registerSingleton(RouterInterface::class, Router::class);
        return $this;
    }
}