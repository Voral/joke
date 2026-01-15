<?php

namespace Vasoft\Joke\Core\Routing;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use Vasoft\Joke\Core\Collections\PropsCollection;
use Vasoft\Joke\Core\Exceptions\InvalidArgumentException;
use Vasoft\Joke\Core\Routing\Exceptions\AutowiredException;

class ParameterResolver
{
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
}