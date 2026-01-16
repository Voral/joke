<?php

namespace Vasoft\Joke\Core\Routing;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use Vasoft\Joke\Core\Routing\Exceptions\AutowiredException;
use Vasoft\Joke\Core\ServiceContainer;

/**
 * @todo Кеширование
 */
class ParameterResolver
{
    public function __construct(private readonly ServiceContainer $serviceContainer) { }

    /**
     * @param callable|string|array $callable
     * @return ReflectionFunctionAbstract
     * @throws \ReflectionException
     */
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
     * @param array<ReflectionParameter> $parameters
     * @param array<string,mixed> $context
     * @return array
     * @throws AutowiredException
     */
    protected function resolveProps(array $parameters, array $context = []): array
    {
        $args = [];
        foreach ($parameters as $param) {
            $name = $param->getName();
            $type = $param->getType()?->getName();

            if (isset($context[$name])) {
                if ($type && class_exists($type)) {
                    if (method_exists($type, 'tryFrom')) {
                        $args[] = $type::tryFrom($context[$name]);
                    } else {
                        throw new AutowiredException($name, $type);
                    }
                } else {
                    $args[] = $context[$name];
                }
                continue;
            }
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
     * @param callable|string|array $callable
     * @param array $context
     * @return array
     * @throws AutowiredException
     * @throws \ReflectionException
     */
    public function resolveForCallable(callable|string|array $callable, array $context = []): array
    {
        $reflection = $this->getCallableReflection($callable);
        return $this->resolveProps($reflection->getParameters(), $context);
    }

    /**
     * @param string $className
     * @param array $context
     * @return array
     * @throws AutowiredException
     * @throws \ReflectionException
     */
    public function resolveForConstructor(string $className, array $context = []): array
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return [];
        }
        return $this->resolveProps($constructor->getParameters(), $context);
    }
}