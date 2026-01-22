<?php

namespace Vasoft\Joke\Contract\Core\Routing;

use Vasoft\Joke\Core\Exceptions\ParameterResolveException;

/**
 * Автоматическое связывание параметров
 *
 * Используется для автоматического связывание параметров по сигнатуре. Анализируется сигнатура и формируется массив параметров.
 */
interface ResolverInterface
{
    /**
     * Связывание парамеров функций и методов.
     * @param callable|string|array $callable функция или метод
     * @param array<string,mixed> $context Массив переменных контекста
     * @return array
     * @throws ParameterResolveException
     */
    public function resolveForCallable(callable|string|array $callable, array $context = []): array;

    /**
     * Связывание парамеров конструкторов
     * @param string $className Имя класса
     * @param array<string,mixed> $context Массив переменных контекста
     * @return array
     * @throws ParameterResolveException
     */
    public function resolveForConstructor(string $className, array $context = []): array;
}