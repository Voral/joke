<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Contract\Core\ApplicationContainerInterface;
use Vasoft\Joke\Contract\Core\Routing\RouterInterface;
use Vasoft\Joke\Core\Exceptions\JokeException;
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
     * @throws Exceptions\ParameterResolveException
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
