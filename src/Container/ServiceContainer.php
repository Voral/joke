<?php

declare(strict_types=1);

namespace Vasoft\Joke\Container;

use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Contract\Container\ApplicationContainerInterface;
use Vasoft\Joke\Contract\Routing\RouterInterface;
use Vasoft\Joke\Exceptions\JokeException;
use Vasoft\Joke\Routing\Router;

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
     * @throws ParameterResolveException
     * @throws JokeException
     */
    public function getRouter(): RouterInterface
    {
        $result = $this->get(RouterInterface::class);
        if (!$result instanceof RouterInterface) {
            throw new JokeException('Router service is not available or not of correct type.');
        }

        return $result;
    }

    public function setRouter(callable|object|string $router): static
    {
        $this->registerSingleton(RouterInterface::class, Router::class);

        return $this;
    }
}
