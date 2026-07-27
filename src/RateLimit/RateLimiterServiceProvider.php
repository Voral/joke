<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimit;

use Vasoft\Joke\Contract\Provider\ConfigurableServiceProviderInterface;
use Vasoft\Joke\Contract\RateLimit\ClientIdentifierInterface;
use Vasoft\Joke\Contract\RateLimit\StorageInterface;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\RateLimit\IpClientIdentifier;
use Vasoft\Joke\RateLimit\RateLimitConfig;
use Vasoft\Joke\Storage\FileBasedStorage;

final class RateLimiterServiceProvider extends AbstractProvider implements ConfigurableServiceProviderInterface
{
    public function register(): void
    {
        $config = $this->container->get(RateLimitConfig::class);

        $this->container->bind(
            StorageInterface::class,
            fn () => new FileBasedStorage($config->storagePath)
        );

        $this->container->bind(
            ClientIdentifierInterface::class,
            fn () => new IpClientIdentifier()
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
}
