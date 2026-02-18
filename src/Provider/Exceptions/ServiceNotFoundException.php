<?php

declare(strict_types=1);

namespace Vasoft\Joke\Provider\Exceptions;

/**
 * Выбрасывается, когда провайдер объявляет зависимость от сервиса,
 * который не может быть предоставлен ни одним зарегистрированным провайдером
 * и отсутствует в контейнере.
 */
class ServiceNotFoundException extends ProviderException
{
    /**
     * @param class-string $providerClass класс провайдера, у которого возникла проблема
     * @param class-string $serviceClass  имя требуемого сервиса, который не найден
     */
    public function __construct(
        string $providerClass,
        string $serviceClass,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            "Provider {$providerClass} requires '{$serviceClass}', but no provider was found for it "
            . 'and it is not registered in the container.',
            $code,
            $previous,
        );
    }
}
