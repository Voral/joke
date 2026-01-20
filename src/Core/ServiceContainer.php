<?php

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Contract\Core\Routing\ResolverInterface;
use Vasoft\Joke\Contract\Core\Routing\RouterInterface;
use Vasoft\Joke\Core\Routing\Exceptions\AutowiredException;
use Vasoft\Joke\Core\Routing\ParameterResolver;
use Vasoft\Joke\Core\Routing\Router;

/**
 * Контейнер внедрения зависимостей (DI Container).
 *
 * Управляет жизненным циклом сервисов, поддерживает синглтоны и прототипы,
 * автоматически разрешает зависимости через рефлексию и интегрируется
 * с системой маршрутизации фреймворка.
 */
class ServiceContainer
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
     * Регистрирует стандартные сервисы и добавляет сам себя в список синглтонов.
     */
    public function __construct()
    {
        $this->initDefault();
        $this->singletons[self::class] = $this;
    }
    /**
     * Инициализирует стандартные сервисы по умолчанию.
     *
     * Регистрирует реализации ResolverInterface и RouterInterface.
     */
    protected function initDefault(): void
    {
        $this->registerSingleton(ResolverInterface::class, ParameterResolver::class);
        $this->registerSingleton(RouterInterface::class, Router::class);
    }

    /**
     * Возвращает экземпляр резолвера параметров.
     *
     * Гарантирует, что резолвер создаётся только один раз и не вызывает
     * рекурсивного разрешения зависимостей.
     *
     * @return ResolverInterface
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
     * Регистрирует сервис как синглтон.
     *
     * Сервис будет создан один раз при первом обращении и переиспользоваться во всех последующих вызовах.
     *
     * @param string $name Имя сервиса (обычно интерфейс или абстрактный класс)
     * @param callable|string|object $service Определение сервиса:
     *        - строка с именем класса
     *        - callable (фабрика)
     *        - готовый объект
     */
    public function registerSingleton(string $name, callable|string|object $service): void
    {
        $this->singletonsRegistry[$name] = $service;
        if (is_object($service) && !($service instanceof \Closure)) {
            $this->singletons[$name] = $service;
        }
    }
    /**
     * Регистрирует сервис как прототип.
     *
     * При каждом вызове get() будет создаваться новый экземпляр.
     * Если передан готовый объект (не callable), он автоматически регистрируется как синглтон.
     *
     * @param string $name Имя сервиса
     * @param callable|string|object $service Определение сервиса
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
     * Получает экземпляр сервиса.
     *
     * Сначала ищет среди синглтонов, затем среди прототипов.
     * Возвращает null, если сервис не зарегистрирован.
     *
     * @param string $name Имя сервиса
     * @return object|null Экземпляр сервиса или null, если не найден
     * @throws AutowiredException Если не удаётся разрешить зависимости
     * @throws \ReflectionException При ошибках рефлексии
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
     * @throws AutowiredException Если не удаётся разрешить зависимости
     * @throws \ReflectionException При ошибках рефлексии
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
     * @throws AutowiredException Если не удаётся разрешить зависимости
     * @throws \ReflectionException При ошибках рефлексии
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