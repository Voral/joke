<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter\Provider;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\ConfigManager;
use Vasoft\Joke\Config\Exceptions\UnknownConfigException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\Contract\RateLimiter\RateLimiterInterface;
use Vasoft\Joke\Contract\Storage\StorageInterface;
use Vasoft\Joke\Contract\Provider\ConfigurableServiceProviderInterface;
use Vasoft\Joke\Middleware\StdMiddleware;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\RateLimiter\ClientIdentifier\IpClientIdentifier;
use Vasoft\Joke\RateLimiter\Middleware\RateLimiterMiddleware;
use Vasoft\Joke\RateLimiter\RateLimiterConfig;
use Vasoft\Joke\RateLimiter\SlidingWindowRateLimiter;
use Vasoft\Joke\Storage\FileBasedStorage;

/**
 * Сервис-провайдер для системы Rate Limiting.
 *
 * Регистрирует в DI-контейнере:
 * - StorageInterface (FileBasedStorage)
 * - ClientIdentifierInterface (IpClientIdentifier по умолчанию)
 * - RateLimiterInterface (SlidingWindowRateLimiter)
 * - RateLimiterMiddleware
 *
 * @see ConfigurableServiceProviderInterface
 */
class RateLimiterServiceProvider extends AbstractProvider implements ConfigurableServiceProviderInterface
{
    public function __construct(
        private readonly ServiceContainer $serviceContainer,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        // Регистрируем фабрики (lazy initialization).
        // Конфиг ещё может быть не загружен на этом этапе,
        // поэтому создаём сервисы при первом обращении через замыкания.
        $this->serviceContainer->registerSingleton(
            StorageInterface::class,
            function (): StorageInterface {
                $config = $this->getRateLimiterConfig();
                $storagePath = $config->getStoragePath();
                if ('' === $storagePath) {
                    $storagePath = $this->getDefaultStoragePath();
                }

                return new FileBasedStorage($storagePath);
            },
        );

        $this->serviceContainer->registerSingleton(
            ClientIdentifierInterface::class,
            function (): ClientIdentifierInterface {
                $config = $this->getRateLimiterConfig();
                $identifierClass = $config->getClientIdentifierClass();
                if ('' !== $identifierClass && class_exists($identifierClass)) {
                    return new $identifierClass();
                }

                return new IpClientIdentifier();
            },
        );

        $this->serviceContainer->registerSingleton(
            RateLimiterInterface::class,
            function (): RateLimiterInterface {
                $storage = $this->serviceContainer->get(StorageInterface::class);
                $identifier = $this->serviceContainer->get(ClientIdentifierInterface::class);
                $config = $this->getRateLimiterConfig();

                return new SlidingWindowRateLimiter($storage, $identifier, $config->getRules());
            },
        );
    }

    /**
     * {@inheritDoc}
     */
    public function boot(): void
    {
        /** @var \Vasoft\Joke\Middleware\MiddlewareCollection $routeMiddlewares */
        $routeMiddlewares = $this->serviceContainer->get('middleware.route');

        $routeMiddlewares->addMiddleware(
            RateLimiterMiddleware::class,
            StdMiddleware::RATE_LIMITER->value,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function provides(): array
    {
        return [
            StorageInterface::class,
            ClientIdentifierInterface::class,
            RateLimiterInterface::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function provideConfigs(): array
    {
        return [
            RateLimiterConfig::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function buildConfig(string $configClass, ServiceContainer $container): AbstractConfig
    {
        return match ($configClass) {
            RateLimiterConfig::class => (new RateLimiterConfig())
                ->setRules([
                    'default' => ['limit' => 100, 'window' => 60],
                ]),
            default => throw new UnknownConfigException($configClass),
        };
    }

    /**
     * Возвращает конфигурацию Rate Limiter через ConfigManager.
     */
    private function getRateLimiterConfig(): RateLimiterConfig
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->serviceContainer->get(ConfigManager::class);

        /** @var RateLimiterConfig $config */
        return $configManager->get(RateLimiterConfig::class);
    }

    /**
     * Возвращает путь к хранилищу по умолчанию.
     */
    private function getDefaultStoragePath(): string
    {
        return getcwd() . '/storage/ratelimit';
    }
}