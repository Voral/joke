<?php

declare(strict_types=1);

namespace Vasoft\Joke\Container;

use Vasoft\Joke\Collections\HeadersCollection;
use Vasoft\Joke\Collections\PropsCollection;
use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Contract\Container\ApplicationContainerInterface;
use Vasoft\Joke\Contract\Container\ContainerInspectionInterface;
use Vasoft\Joke\Contract\Container\DiContainerInterface;
use Vasoft\Joke\Contract\Container\ResolverInterface;
use Vasoft\Joke\Container\Exceptions\ContainerException;
use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Contract\Routing\RouteInterface;
use Vasoft\Joke\Contract\Routing\RouterInterface;
use Vasoft\Joke\Core as LegacyCore;
use Vasoft\Joke\Contract\Core as LegacyContract;
use Vasoft\Joke\Foundation\Request;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\BinaryResponse;
use Vasoft\Joke\Http\Response\HtmlResponse;
use Vasoft\Joke\Http\Response\JsonResponse;
use Vasoft\Joke\Http\Response\Response;
use Vasoft\Joke\Http\ServerCollection;
use Vasoft\Joke\Routing\Route;
use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Session\SessionCollection;

/**
 * Базовый контейнер внедрения зависимостей (DI Container).
 *
 * Управляет жизненным циклом сервисов, поддерживает синглтоны и прототипы,
 * автоматически разрешает зависимости через рефлексию.
 */
abstract class BaseContainer implements ContainerInspectionInterface
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
    private array $aliases = [];

    /**
     * Конструктор контейнера.
     *
     * Регистрирует стандартные сервисы и самого себя
     */
    public function __construct()
    {
        $this->initDefault();
        $this->singletons[static::class] = $this;
        $this->singletons[DiContainerInterface::class] = $this;
        $this->singletonsRegistry[static::class] = static::class;
        $this->singletonsRegistry[DiContainerInterface::class] = static::class;
    }

    /**
     * Инициализирует стандартные сервисы по умолчанию.
     *
     * Регистрирует реализации ResolverInterface
     */
    protected function initDefault(): void
    {
        $this->registerSingleton(ResolverInterface::class, new ParameterResolver($this));

        $this->registerAlias(LegacyCore\ServiceContainer::class, ServiceContainer::class);
        $this->registerAlias(LegacyCore\BaseContainer::class, self::class);
        $this->registerAlias(LegacyCore\Routing\ParameterResolver::class, ResolverInterface::class);
        $this->registerAlias(LegacyCore\Routing\Route::class, Route::class);
        $this->registerAlias(LegacyCore\Routing\Router::class, Router::class);
        $this->registerAlias(LegacyCore\Request\Request::class, Request::class);
        $this->registerAlias(LegacyCore\Request\HttpRequest::class, HttpRequest::class);
        $this->registerAlias(LegacyCore\Request\ServerCollection::class, ServerCollection::class);
        $this->registerAlias(LegacyCore\Response\Response::class, Response::class);
        $this->registerAlias(LegacyCore\Response\HtmlResponse::class, HtmlResponse::class);
        $this->registerAlias(LegacyCore\Response\JsonResponse::class, JsonResponse::class);
        $this->registerAlias(LegacyCore\Response\BinaryResponse::class, BinaryResponse::class);
        $this->registerAlias(LegacyCore\Collections\Session::class, SessionCollection::class);
        $this->registerAlias(LegacyCore\Collections\HeadersCollection::class, HeadersCollection::class);
        $this->registerAlias(LegacyCore\Collections\PropsCollection::class, PropsCollection::class);
        $this->registerAlias(LegacyContract\ApplicationContainerInterface::class, ApplicationContainerInterface::class);
        $this->registerAlias(LegacyContract\DiContainerInterface::class, DiContainerInterface::class);
        $this->registerAlias(LegacyContract\Middlewares\MiddlewareInterface::class, MiddlewareInterface::class);
        $this->registerAlias(LegacyContract\Routing\ResolverInterface::class, ResolverInterface::class);
        $this->registerAlias(LegacyContract\Routing\RouteInterface::class, RouteInterface::class);
        $this->registerAlias(LegacyContract\Routing\RouterInterface::class, RouterInterface::class);
    }

    public function getParameterResolver(): ResolverInterface
    {
        /** @var ResolverInterface $resolver */
        return $this->getSingleton(ResolverInterface::class);
    }

    public function registerSingleton(string $name, callable|object|string $service): void
    {
        $this->singletonsRegistry[$name] = $service;
        if (is_object($service) && !($service instanceof \Closure)) {
            $this->singletons[$name] = $service;
        }
    }

    /**
     * @deprecated: Передача вызываемых объектов (с помощью __invoke) будет рассматриваться как синглтоны в версии 2.0.
     *  Используйте \Closure для фабрик.
     */
    public function register(string $name, callable|object|string $service): void
    {
        if (str_contains($name, '\\') && !$this->isValidServiceName($name)) {
            trigger_error("Service name '{$name}' is not a valid class or interface.", E_USER_WARNING);
        }
        if (is_object($service)) {
            if (!is_callable($service)) {
                $this->registerSingleton($name, $service);
            } else {
                @trigger_error(
                    'Passing a callable object to register() is deprecated. '
                    . 'In v2.0 it will be treated as a singleton. '
                    . 'Use a Closure for factories: fn() => $obj().',
                    E_USER_DEPRECATED,
                );
                $this->serviceRegistry[$name] = $service;
            }
        } else {
            $this->serviceRegistry[$name] = $service;
        }
    }

    private function isValidServiceName(string $name): bool
    {
        if (!str_contains($name, '\\')) {
            return true;
        }

        return interface_exists($name) || class_exists($name);
    }

    public function get(string $name): ?object
    {
        $result = $this->getSingleton($name);
        if (null !== $result) {
            return $result;
        }
        $result = $this->getService($name);
        if (null === $result) {
            @trigger_error(
                "Service '{$name}' not found. In v2.0 this will throw an exception.",
                E_USER_DEPRECATED,
            );
        }

        return $result;
    }

    /**
     * Создаёт новый экземпляр прототипа.
     *
     * @param string $name Имя сервиса
     *
     * @return null|object Экземпляр сервиса или null, если не зарегистрирован
     *
     * @throws ParameterResolveException При ошибках
     */
    private function getService(string $name): ?object
    {
        ['definition' => $definition] = $this->resolveEntry($name, $this->serviceRegistry);
        if (null === $definition) {
            return null;
        }

        return $this->buildFromDefinition($definition);
    }

    private function buildFromDefinition(callable|string $definition): object
    {
        $resolver = $this->getParameterResolver();
        if (is_callable($definition)) {
            $args = $resolver->resolveForCallable($definition);

            return $definition(...$args);
        }
        $args = $resolver->resolveForConstructor($definition);

        return new $definition(...$args);
    }

    /**
     * Получает или создаёт синглтон.
     *
     * @param string $name Имя сервиса
     *
     * @return null|object Экземпляр сервиса или null, если не зарегистрирован
     *
     * @throws ParameterResolveException При ошибках рефлексии
     */
    private function getSingleton(string $name): ?object
    {
        if (isset($this->singletons[$name])) {
            return $this->singletons[$name];
        }
        ['definition' => $definition, 'canonicalName' => $canonicalName] = $this->resolveEntry(
            $name,
            $this->singletonsRegistry,
        );
        if (null !== $canonicalName && $name !== $canonicalName && isset($this->singletons[$canonicalName])) {
            $this->singletons[$name] = $this->singletons[$canonicalName];

            return $this->singletons[$name];
        }
        if (null === $definition) {
            return null;
        }
        $entity = $this->buildFromDefinition($definition);
        $this->singletons[$name] = $entity;
        if ($name !== $canonicalName) {
            $this->singletons[$canonicalName] = $entity;
        }

        return $this->singletons[$name];
    }

    /**
     * Разрешает имя сервиса до определения и канонического имени.
     *
     * Выполняет поиск напрямую в реестре. Если не найдено — следует по цепочке алиасов.
     * Прекращает обход при первом совпадении в реестре.
     *
     * Защищён от циклических алиасов через массив $visited.
     *
     * @param string $name     Запрашиваемое имя (может быть алиасом)
     * @param array  $registry Реестр сервисов (прототипов или синглтонов)
     *
     * @return array{definition: null|callable|string, canonicalName: null|string}
     *
     * @throws ContainerException если обнаружена циклическая зависимость алиасов
     */
    private function resolveEntry(string $name, array $registry): array
    {
        $visited = [];
        $current = $name;
        if (array_key_exists($name, $registry)) {
            return ['definition' => $registry[$name], 'canonicalName' => $name];
        }
        while (isset($this->aliases[$current])) {
            if (isset($visited[$current])) {
                throw new ContainerException('Circular alias detected:' . implode('-', array_keys($visited)));
            }
            $visited[$current] = true;
            $current = $this->aliases[$current];
            if (array_key_exists($current, $registry)) {
                return ['definition' => $registry[$current], 'canonicalName' => $current];
            }
        }

        return ['definition' => null, 'canonicalName' => null];
    }

    /**
     * Регистрирует алиас для имени сервиса.
     *
     * Поддерживает цепочки алиасов (например: 'a' → 'b' → Service::class).
     * При разрешении запроса по алиасу контейнер рекурсивно следует по цепочке,
     * пока не найдёт зарегистрированный сервис или не достигнет конца.
     *
     * Циклические зависимости (например: 'a' → 'b', 'b' → 'a') приводят к выбросу
     * {@see ContainerException}.
     *
     * @param string $alias    Псевдоним, под которым будет доступен сервис
     * @param string $concrete Имя сервиса, интерфейса или другого алиаса
     */
    public function registerAlias(string $alias, string $concrete): static
    {
        if (str_contains($concrete, '\\') && !$this->isValidServiceName($concrete)) {
            trigger_error("Service name '{$concrete}' is not a valid class or interface.", E_USER_WARNING);
        }
        $this->aliases[$alias] = $concrete;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->definitions[$name]) || isset($this->instances[$name]);
    }
}
