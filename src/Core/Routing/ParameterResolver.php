<?php

namespace Vasoft\Joke\Core\Routing;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use Vasoft\Joke\Core\Collections\PropsCollection;
use Vasoft\Joke\Core\Exceptions\InvalidArgumentException;
use Vasoft\Joke\Core\Routing\Exceptions\AutowiredException;
use Vasoft\Joke\Core\ServiceContainer;

class ParameterResolver
{
    public function __construct(private readonly ServiceContainer $serviceContainer) { }

    private function getCallableReflection(callable|string|array $callable): ReflectionFunctionAbstract
    {
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        if (is_string($callable)) {
            if (str_contains($callable, '::')) {
                [$class, $method] = explode('::', $callable, 2);
                return new \ReflectionMethod($class, $method);
            } else {
                return new ReflectionFunction($callable);
            }
        }

        [$target, $method] = $callable;

        return new \ReflectionMethod($target, $method);
    }

    /**
     * @param PropsCollection $parameters
     * @param $callback
     * @return array
     * @throws AutowiredException
     */
    public function resolve(PropsCollection $parameters, $callback): array
    {
        $args = [];
        $reflection = $this->getCallableReflection($callback);
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType()?->getName();

            $value = $parameters->get($name);
            if ($type && class_exists($type)) {
                if (method_exists($type, 'tryFrom')) {
                    $args[] = $type::tryFrom($value);
                } else {
                    throw new AutowiredException($name, $type);
                }
            } else {
                $args[] = $value;
            }
        }
        return $args;
    }

    public function resolveForCallable(callable|string|array $callable, array $context = []): array
    {
        $reflection = $this->getCallableReflection($callable);
        $args = [];

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType()?->getName();

            if ($type && class_exists($type)) {
                $service = $this->serviceContainer->get($type);
                if ($service === null) {
                    throw new AutowiredException($name, $type);
                }
                $args[] = $service;
            } else {
                throw new AutowiredException($name, $type ?: 'scalar');
            }
        }

        return $args;
    }

    /**
     * @return array
     * @throws AutowiredException
     */
    public function resolveForService(string|object $serviceDefinition): array
    {
        if (is_object($serviceDefinition)) {
            $className = $serviceDefinition::class;
        } elseif (is_string($serviceDefinition)) {
            if (!class_exists($serviceDefinition)) {
//                throw new AutowiredException("Service definition is not a valid class: $serviceDefinition");
                throw new AutowiredException('todo', 'fixit');
            }
            $className = $serviceDefinition;
        } else {
//            throw new AutowiredException("Invalid service definition type");
            throw new AutowiredException('todo', 'fixit');
        }
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return []; // Нет конструктора — нет зависимостей
        }
        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType()?->getName();
            $service = $this->serviceContainer->get($type);
            if ($service === null) {
                throw new AutowiredException($name, $type);
            }
            $args[] = $service;
        }
        return $args;
    }
}