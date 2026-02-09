<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Routing;

use Vasoft\Joke\Contract\Core\Routing\ResolverInterface;
use Vasoft\Joke\Core\Exceptions\ParameterResolveException;
use Vasoft\Joke\Core\Routing\Exceptions\AutowiredException;
use Vasoft\Joke\Core\ServiceContainer;

/**
 * Реализация резолвера параметров для автоматического связывания зависимостей.
 *
 * Анализирует сигнатуру callable-значений и конструкторов, разрешая параметры
 * из двух источников в порядке приоритета:
 * 1. Контекст (например, параметры маршрута, переменные запроса)
 * 2. DI-контейнер (зарегистрированные сервисы)
 *
 * Поддерживает автоматическую десериализацию backed enum через tryFrom().
 *
 * @todo Реализовать кэширование результатов рефлексии для повышения производительности
 * @todo Добавить поддержку параметров со значениями по умолчанию
 */
class ParameterResolver implements ResolverInterface
{
    /**
     * Конструктор резолвера.
     *
     * @param ServiceContainer $serviceContainer DI-контейнер для разрешения сервисов
     */
    public function __construct(private readonly ServiceContainer $serviceContainer) {}

    /**
     * Создаёт объект рефлексии для заданного callable.
     *
     * Поддерживает все формы callable: замыкания, строки вида 'Class::method',
     * массивы [Class::class, 'method'].
     *
     * @param array{class-string,non-empty-string}|callable|string $callable Целевой callable для анализа
     *
     * @return \ReflectionFunctionAbstract Объект рефлексии функции или метода
     *
     * @throws ParameterResolveException
     */
    private function getCallableReflection(array|callable|string $callable): \ReflectionFunctionAbstract
    {
        try {
            if ($callable instanceof \Closure) {
                return new \ReflectionFunction($callable);
            }

            if (is_string($callable)) {
                if (str_contains($callable, '::')) {
                    [$class, $method] = explode('::', $callable, 2);

                    return new \ReflectionMethod($class, $method);
                }

                return new \ReflectionFunction($callable);
            }

            [$target, $method] = $callable;
            $result = new \ReflectionMethod($target, $method);
        } catch (\ReflectionException $e) {
            throw new ParameterResolveException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }

    /**
     * Разрешает параметры на основе их типов и контекста.
     *
     * Для каждого параметра:
     * - если имя совпадает с ключом в контексте → использует значение из контекста
     *   - если тип — backed enum → вызывает tryFrom()
     *   - иначе → использует значение как есть
     * - если имя не найдено в контексте, но тип — класс → запрашивает сервис из DI-контейнера
     * - иначе → выбрасывает исключение
     *
     * @param array<\ReflectionParameter> $parameters Список параметров для разрешения
     * @param array<string, mixed>        $context    Контекстные переменные (например, параметры маршрута)
     *
     * @return list<mixed> Массив разрешённых аргументов
     *
     * @throws ParameterResolveException Если параметр не может быть разрешён
     *
     * @todo Декомпозировать метод
     */
    protected function resolveProps(array $parameters, array $context = []): array
    {
        $args = [];
        foreach ($parameters as $param) {
            $name = $param->getName();
            $type = $this->getTypeName($param->getType());
            // todo Приведение к float и int временное решение, надо учитывать юнион типы
            if (isset($context[$name])) {
                if ($type && class_exists($type)) {
                    if (method_exists($type, 'tryFrom')) {
                        $args[] = $type::tryFrom($context[$name]);
                    } else {
                        throw new AutowiredException($name, $type);
                    }
                } elseif ('float' === $type) {
                    $args[] = (float) $context[$name];
                } elseif ('int' === $type) {
                    $args[] = (int) $context[$name];
                } else {
                    $args[] = $context[$name];
                }

                continue;
            }
            if ($type && class_exists($type)) {
                $service = $this->serviceContainer->get($type);
                if (null === $service) {
                    throw new AutowiredException($name, $type);
                }
                $args[] = $service;
            } else {
                throw new AutowiredException($name, $type ?: 'scalar');
            }
        }

        return $args;
    }

    private function getTypeName(
        ?\ReflectionType $type,
    ): ?string {
        $typeName = null;
        if (null !== $type) {
            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
            } elseif ($type instanceof \ReflectionUnionType) {
                $types = [];
                foreach ($type->getTypes() as $unionType) {
                    if ($unionType instanceof \ReflectionNamedType) {
                        $types[] = $unionType->getName();
                    }
                }
                $typeName = implode('|', $types);
            } elseif ($type instanceof \ReflectionIntersectionType) {
                // Для пересечений типов
                $types = [];
                foreach ($type->getTypes() as $intersectionType) {
                    if ($intersectionType instanceof \ReflectionNamedType) {
                        $types[] = $intersectionType->getName();
                    }
                }
                $typeName = implode('&', $types);
            }
        }

        return $typeName;
    }

    public function resolveForCallable(array|callable|string $callable, array $context = []): array
    {
        $reflection = $this->getCallableReflection($callable);

        return $this->resolveProps($reflection->getParameters(), $context);
    }

    public function resolveForConstructor(string $className, array $context = []): array
    {
        try {
            $reflection = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new ParameterResolveException($e->getMessage(), $e->getCode(), $e);
        }
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            return [];
        }

        return $this->resolveProps($constructor->getParameters(), $context);
    }
}
