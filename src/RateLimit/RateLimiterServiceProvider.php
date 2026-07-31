<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimit;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\UnknownConfigException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Provider\ConfigurableServiceProviderInterface;
use Vasoft\Joke\Contract\RateLimit\ClientIdentifierInterface;
use Vasoft\Joke\Contract\RateLimit\StorageInterface;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\Storage\FileBasedStorage;

final class RateLimiterServiceProvider extends AbstractProvider implements ConfigurableServiceProviderInterface
{
    public function __construct(
        private readonly ServiceContainer $serviceContainer,
    ) {}
    public function register(): void
    {
        $config = $this->serviceContainer->get(RateLimitConfig::class);

        $this->serviceContainer->registerSingleton(
            StorageInterface::class,
            static fn() => new FileBasedStorage($config->storagePath),
        );

        $this->serviceContainer->registerSingleton(
            ClientIdentifierInterface::class,
            static fn() => new IpClientIdentifier(),
        );
    }

    public function boot(): void
    {
        // Middleware будет зарегистрирована через конфиг приложения
    }

    public function provides(): array
    {
        return [
            StorageInterface::class,
            ClientIdentifierInterface::class,
        ];
    }

    public static function provideConfigs(): array
    {
        return [RateLimitConfig::class];
    }

    public static function defaultFor(string $configClass): ?object
    {
        return match ($configClass) {
            RateLimitConfig::class => new RateLimitConfig(),
            default => null,
        };
    }

    public static function buildConfig(string $configClass, ServiceContainer $container): AbstractConfig
    {
        return match ($configClass) {
            RateLimitConfig::class => new RateLimitConfig(),
            default => throw new UnknownConfigException($configClass),
        };
    }
}
