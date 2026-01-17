<?php

namespace Vasoft\Joke\Core\Routing;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use Vasoft\Joke\Contract\Core\Routing\ResolverInterface;
use Vasoft\Joke\Core\Routing\Exceptions\AutowiredException;
use Vasoft\Joke\Core\ServiceContainer;

/**
 * Реализация связывания параметров
 *
 * Кроме передаваемых переменных контекста анализируется и контейнер внедрения зависимостей
 * При связывании в первую очередь поиск значений происходит в контексте, далее, если ожидается объект,
 * проверяется наличие необходимой реализации в DI контейнере
 * @todo Кеширование рефлексии
 * @todo Учет параметров по умолчанию и вероятность ситуации, где один параметр со значением по умолчанию пропущен, а следующий есть в контексте
 */
class ParameterResolver
    implements ResolverInterface
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
     * @inherit
     * @throws \ReflectionException
     * @todo Привести исключения рефлексии к исключению авто-связывания
     */
    public function resolveForCallable(callable|string|array $callable, array $context = []): array
    {
        $reflection = $this->getCallableReflection($callable);
        return $this->resolveProps($reflection->getParameters(), $context);
    }

    /**
     * @inherit
     * @throws \ReflectionException
     * @todo Привести исключения рефлексии к исключению авто-связывания
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