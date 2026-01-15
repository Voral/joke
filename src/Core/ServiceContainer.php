<?php

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Core\Routing\ParameterResolver;

class ServiceContainer
{
    /**
     * @var array<string,callable|string>
     */
    private array $serviceRegistry = [];
    /**
     * /**
     * @var array<string,callable|string>
     */
    private array $singletonsRegistry = [];
    /**
     * @var array<string,object>
     */
    private array $singletons = [];
    private bool $lockResolver = false;

    public function __construct()
    {
        $this->initDefault();
        $this->singletons[self::class] = $this;
    }

    protected function initDefault(): void
    {
        $this->registerSingleton(ParameterResolver::class, ParameterResolver::class);
    }

    public function getParameterResolver(): ParameterResolver
    {
        if (isset($this->singletons[ParameterResolver::class])) {
            /** @var ParameterResolver $resolver */
            $resolver = $this->singletons[ParameterResolver::class];
            return $resolver;
        }
        $definition = $this->singletonsRegistry[ParameterResolver::class] ?? ParameterResolver::class;
        $this->singletons[ParameterResolver::class] = new $definition($this);
        return $this->singletons[ParameterResolver::class];
    }


    public function registerSingleton(string $name, callable|string|object $service): void
    {
        $this->singletonsRegistry[$name] = $service;
        if (is_object($service) && !($service instanceof \Closure)) {
            $this->singletons[$name] = $service;
        }
    }

    public function register(string $name, callable|string|object $service): void
    {
        if (is_object($service)) {
            $this->registerSingleton($name, $service);
        } else {
            $this->serviceRegistry[$name] = $service;
        }
    }

    /**
     * @param string $name
     * @return ?object
     * @throws Routing\Exceptions\AutowiredException
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
     * @param string $name
     * @return ?object
     * @throws Routing\Exceptions\AutowiredException
     */
    private function getService(string $name): ?object
    {
        if (!isset($this->serviceRegistry[$name])) {
            return null;
        }
        $resolver = $this->getParameterResolver();
        $args = $resolver->resolveForService($this->serviceRegistry[$name]);
        if (is_callable($this->serviceRegistry[$name])) {
            return $this->serviceRegistry[$name](...$args);
        }
        return new $this->serviceRegistry[$name](...$args);
    }

    /**
     * @param string $name
     * @return ?object
     * @throws Routing\Exceptions\AutowiredException
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
            if (is_string($definition)) {
                $args = $resolver->resolveForService($definition);
            } elseif (is_callable($definition)) {
                $args = $resolver->resolveForCallable($definition);
            } else {
                throw new \LogicException("Unsupported definition type for '$name'");
            }
        }
        if (is_object($definition)) {
            $this->singletons[$name] = $definition;
        } elseif (is_callable($this->singletonsRegistry[$name])) {
            $this->singletons[$name] = $definition(...$args);
        } else {
            if (!class_exists($definition)) {
                throw new \RuntimeException("Class $definition does not exist");
            }
            $this->singletons[$name] = new $definition(...$args);
        }
        return $this->singletons[$name];
    }
}