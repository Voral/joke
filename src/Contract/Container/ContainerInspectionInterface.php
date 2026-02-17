<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Container;

/**
 * Расширенное описание DI контейнера.
 *
 * В версии 2.0 методы будут перенесены в DiContainerInterface, а данный интерфейс будет помечен Deprecated
 */
interface ContainerInspectionInterface extends DiContainerInterface
{
    /**
     * Проверяет наличие сервиса в контейнере без его создания.
     */
    public function has(string $name): bool;
}
