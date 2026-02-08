<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Core;

use Vasoft\Joke\Contract\Core\Routing\ResolverInterface;
use Vasoft\Joke\Core\Exceptions\ParameterResolveException;

interface DiContainerInterface
{
    /**
     * Возвращает экземпляр резолвера параметров.
     *
     * Гарантирует, что резолвер создаётся только один раз и не вызывает
     * рекурсивного разрешения зависимостей.
     */
    public function getParameterResolver(): ResolverInterface;

    /**
     * Регистрирует сервис как синглтон.
     *
     * Сервис будет создан один раз при первом обращении и переиспользоваться во всех последующих вызовах.
     *
     * @param string                 $name    Имя сервиса (обычно интерфейс или абстрактный класс)
     * @param callable|object|string $service Определение сервиса:
     *                                        - строка с именем класса
     *                                        - callable (фабрика)
     *                                        - готовый объект
     */
    public function registerSingleton(string $name, callable|object|string $service): void;

    /**
     * Регистрирует сервис как прототип.
     *
     * При каждом вызове get() будет создаваться новый экземпляр.
     * Если передан готовый объект (не callable), он автоматически регистрируется как синглтон.
     *
     * @param string                 $name    Имя сервиса
     * @param callable|object|string $service Определение сервиса
     */
    public function register(string $name, callable|object|string $service): void;

    /**
     * Получает экземпляр сервиса.
     *
     * Сначала ищет среди синглтонов, затем среди прототипов.
     * Возвращает null, если сервис не зарегистрирован.
     *
     * @param string $name Имя сервиса
     *
     * @return null|object Экземпляр сервиса или null, если не найден
     *
     * @throws ParameterResolveException Если не удаётся разрешить зависимости
     */
    public function get(string $name): ?object;
}
