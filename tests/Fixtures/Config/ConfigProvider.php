<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Config;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\UnknownConfigException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Provider\ConfigurableServiceProviderInterface;

class ConfigProvider implements ConfigurableServiceProviderInterface
{
    public static function provideConfigs(): array
    {
        return [SecondSingleConfig::class, 'wrong'];
    }

    public static function buildConfig(string $configClass, ServiceContainer $container): AbstractConfig
    {
        if (SecondSingleConfig::class === $configClass) {
            return new SecondSingleConfig('DefaultBuilder');
        }
        if ('wrong' === $configClass) {
            return new SingleConfig();
        }

        throw new UnknownConfigException($configClass);
    }

    public function register(): void
    {
        // empty body
    }

    public function boot(): void
    {
        // empty body
    }

    public function requires(): array
    {
        return [];
    }

    public function provides(): array
    {
        return [];
    }
}
