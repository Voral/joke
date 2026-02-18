<?php

declare(strict_types=1);

namespace Vasoft\Joke\Provider\Exceptions;

/**
 * Выбрасывается, если один и тот же сервис заявлен как предоставляемый
 * несколькими разными провайдерами.
 */
class MultipleProvideException extends ProviderException
{
    /**
     * @param class-string       $serviceName имя сервиса (интерфейса или класса), который дублируется
     * @param list<class-string> $providers   список классов провайдеров, претендующих на сервис
     */
    public function __construct(string $serviceName, array $providers, int $code = 0, ?\Throwable $previous = null)
    {
        $list = implode(', ', $providers);
        parent::__construct(
            "Service '{$serviceName}' is provided by multiple providers: {$list}.",
            $code,
            $previous,
        );
    }
}
