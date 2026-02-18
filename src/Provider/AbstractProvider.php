<?php

declare(strict_types=1);

namespace Vasoft\Joke\Provider;

use Vasoft\Joke\Contract\Provider\ServiceProviderInterface;

/**
 * Базовый абстрактный класс для сервис-провайдеров.
 * Предоставляет реализацию метода requires() по умолчанию (пустой массив),
 * что удобно для обычных провайдеров, не имеющих явных зависимостей.
 */
abstract class AbstractProvider implements ServiceProviderInterface
{
    /**
     * По умолчанию провайдер не требует дополнительных сервисов.
     * Переопределите этот метод в дочернем классе, если необходимы зависимости.
     *
     * {@inheritDoc}
     */
    public function requires(): array
    {
        return [];
    }
}
