<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Core\Routing;

use Vasoft\Joke\Core\Exceptions\ParameterResolveException;

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
     * @param array{class-string,non-empty-string}|callable|string $callable функция или метод
     * @param array<string,mixed>                                  $context  Массив переменных контекста
     *
     * @return list<mixed> Массив разрешенных параметров
     *
     * @throws ParameterResolveException
     */
    public function resolveForCallable(array|callable|string $callable, array $context = []): array;

    /**
     * Связывание парамеров конструкторов.
     *
     * @param string              $className Имя класса
     * @param array<string,mixed> $context   Массив переменных контекста
     *
     * @return list<mixed> Массив разрешенных параметров
     *
     * @throws ParameterResolveException
     */
    public function resolveForConstructor(string $className, array $context = []): array;
}
