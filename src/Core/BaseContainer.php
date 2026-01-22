<?php

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Contract\Core\DiContainerInterface;
use Vasoft\Joke\Contract\Core\Routing\ResolverInterface;
use Vasoft\Joke\Core\Exceptions\ParameterResolveException;
use Vasoft\Joke\Core\Routing\ParameterResolver;

/**
 * Базовый контейнер внедрения зависимостей (DI Container).
 *
 * Управляет жизненным циклом сервисов, поддерживает синглтоны и прототипы,
 * автоматически разрешает зависимости через рефлексию.
 */
abstract class BaseContainer implements DiContainerInterface
{
    /**
     * Регистр прототипов (новый экземпляр при каждом запросе).
     *
     * @var array<string, callable|string>
     */
    private array $serviceRegistry = [];
    /**
     * Регистр определений синглтонов.
     *
     * @var array<string, callable|string>
     */
    private array $singletonsRegistry = [];
    /**
     * Кэш созданных синглтонов.
     *
     * @var array<string, object>
     */
    private array $singletons = [];
    /**
     * Флаг блокировки рекурсивного разрешения резолвера.
     *
     * Используется для предотвращения бесконечной рекурсии при создании резолвера.
     *
     * @var bool
     */
    private bool $lockResolver = false;

    /**
     * Конструктор контейнера.
     *
     * Регистрирует стандартные сервисы и самого себя
     */
    public function __construct()
    {
        $this->initDefault();
        $this->singletons[static::class] = $this;
    }

    /**
     * Инициализирует стандартные сервисы по умолчанию.
     *
     * Регистрирует реализации ResolverInterface
     */
    protected function initDefault(): void
    {
        $this->registerSingleton(ResolverInterface::class, ParameterResolver::class);
    }

    /**
     * @inheritDoc
     */
    public function getParameterResolver(): ResolverInterface
    {
        if (isset($this->singletons[ResolverInterface::class])) {
            /** @var ResolverInterface $resolver */
            $resolver = $this->singletons[ResolverInterface::class];
            return $resolver;
        }
        $definition = $this->singletonsRegistry[ResolverInterface::class] ?? ResolverInterface::class;
        $this->singletons[ResolverInterface::class] = new $definition($this);
        return $this->singletons[ResolverInterface::class];
    }


    /**
     * @inheritDoc
     */
    public function registerSingleton(string $name, callable|string|object $service): void
    {
        $this->singletonsRegistry[$name] = $service;
        if (is_object($service) && !($service instanceof \Closure)) {
            $this->singletons[$name] = $service;
        }
    }

    /**
     * @inheritDoc
     */
    public function register(string $name, callable|string|object $service): void
    {
        if (is_object($service) && !is_callable($service)) {
            $this->registerSingleton($name, $service);
        } else {
            $this->serviceRegistry[$name] = $service;
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): ?object
    {
        $result = $this->getSingleton($name);
        if ($result !== null) {
            return $result;
        }
        return $this->getService($name);
    }

    /**
     * Создаёт новый экземпляр прототипа.
     *
     * @param string $name Имя сервиса
     * @return object|null Экземпляр сервиса или null, если не зарегистрирован
     * @throws ParameterResolveException При ошибках
     */
    private function getService(string $name): ?object
    {
        if (!isset($this->serviceRegistry[$name])) {
            return null;
        }
        $resolver = $this->getParameterResolver();
        if (is_callable($this->serviceRegistry[$name])) {
            $args = $resolver->resolveForCallable($this->serviceRegistry[$name]);
        } else {
            $args = $resolver->resolveForConstructor($this->serviceRegistry[$name]);
        }
        if (is_callable($this->serviceRegistry[$name])) {
            return $this->serviceRegistry[$name](...$args);
        }
        return new $this->serviceRegistry[$name](...$args);
    }

    /**
     * Получает или создаёт синглтон.
     *
     * @param string $name Имя сервиса
     * @return object|null Экземпляр сервиса или null, если не зарегистрирован
     * @throws ParameterResolveException При ошибках рефлексии
     */
    private function getSingleton(string $name): ?object
    {
        if (isset($this->singletons[$name])) {
            return $this->singletons[$name];
        }
        if (!isset($this->singletonsRegistry[$name])) {
            return null;
        }
        $definition = $this->singletonsRegistry[$name];
        $args = [];
        if (!$this->lockResolver) {
            $resolver = $this->getParameterResolver();
            if (is_callable($definition)) {
                $args = $resolver->resolveForCallable($definition);
            } else {
                $args = $resolver->resolveForConstructor($definition);
            }
        }

        if (is_callable($this->singletonsRegistry[$name])) {
            $this->singletons[$name] = $definition(...$args);
        } else {
            $this->singletons[$name] = new $definition(...$args);
        }
        return $this->singletons[$name];
    }
}