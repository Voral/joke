<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Container;

use Vasoft\Joke\Container\Exceptions\ParameterResolveException;

/**
 * Автоматическое связывание параметров.
 *
 * Используется для автоматического связывание параметров по сигнатуре. Анализируется сигнатура и формируется массив параметров.
 */
interface ResolverInterface
{
    /**
     * Связывание парамеров функций и методов.
     *
     * @param array{class-string|object,non-empty-string}|object|string $callable функция или метод
     * @param array<string,mixed>                                       $context  Массив переменных контекста
     *
     * @return list<mixed> Массив разрешенных параметров
     *
     * @throws ParameterResolveException
     */
    public function resolveForCallable(array|object|string $callable, array $context = []): array;

    /**
     * Связывание парамеров конструкторов.
     *
     * @param class-string        $className Имя класса
     * @param array<string,mixed> $context   Массив переменных контекста
     *
     * @return list<mixed> Массив разрешенных параметров
     *
     * @throws ParameterResolveException
     */
    public function resolveForConstructor(string $className, array $context = []): array;
}
