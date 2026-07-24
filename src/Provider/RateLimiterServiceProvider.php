<?php

declare(strict_types=1);

namespace Vasoft\Joke\Provider;

use Vasoft\Joke\Contract\Storage\StorageInterface;
use Vasoft\Joke\Contract\Storage\RateLimiterInterface;
use Vasoft\Joke\Contract\Provider\ConfigurableServiceProviderInterface;
use Vasoft\Joke\RateLimiter\SlidingWindowRateLimiter;
use Vasoft\Joke\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\RateLimiter\DefaultClientIdentifier;
use Vasoft\Joke\Storage\FileBasedStorage;
use Vasoft\Joke\Storage\ArrayStorage;
use Vasoft\Joke\Config\RateLimitConfig;
use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Config\Exceptions\UnknownConfigException;

/**
 * Сервис-провайдер для Rate Limiting с поддержкой конфигурации.
 *
 * Регистрирует:
 * - StorageInterface -> FileBasedStorage
 * - RateLimiterInterface -> SlidingWindowRateLimiter
 * - ClientIdentifierInterface -> DefaultClientIdentifier
 */
class RateLimiterServiceProvider extends AbstractProvider implements ConfigurableServiceProviderInterface
{
    /**
     * @var string|null Путь к хранилищу
     */
    private ?string $storagePath = null;

    /**
     * @var string Класс идентификатора клиентов
     */
    private string $clientIdentifierClass = DefaultClientIdentifier::class;

    /**
     * @var string Дра��вер хранилища
     */
    private string $driver = 'file';

    /**
     * Устанавливает путь к хранилищу.
     *
     * @param string $storagePath
     *
     * @return self
     */
    public function setStoragePath(string $storagePath): self
    {
        $this->storagePath = $storagePath;
        
        return $this;
    }

    /**
     * Устанавливает класс идентификатора клиентов.
     *
     * @param class-string<ClientIdentifierInterface> $clientIdentifierClass
     *
     * @return self
     */
    public function setClientIdentifierClass(string $clientIdentifierClass): self
    {
        $this->clientIdentifierClass = $clientIdentifierClass;
        
        return $this;
    }

    /**
     * Устанавливает драйвер хранилища.
     *
     * @param string $driver (file|array|redis)
     *
     * @return self
     */
    public function setDriver(string $driver): self
    {
        $this->driver = $driver;
        
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(StorageInterface::class, function (ServiceContainer $container) {
            return $this->createStorage();
        });

        $container->singleton(RateLimiterInterface::class, function (ServiceContainer $container) {
            $storage = $container->make(StorageInterface::class);
            
            return new SlidingWindowRateLimiter($storage);
        });

        $container->singleton(ClientIdentifierInterface::class, function (ServiceContainer $container) {
            return new $this->clientIdentifierClass();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(): void
    {
        // Провайдер не требует дополнительной инициализации
    }

    /**
     * {@inheritDoc}
     */
    public function provides(): array
    {
        return [
            StorageInterface::class,
            RateLimiterInterface::class,
            ClientIdentifierInterface::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function provideConfigs(): array
    {
        return [RateLimitConfig::class];
    }

    /**
     * {@inheritDoc}
     */
    public static function buildConfig(string $configClass, ServiceContainer $container): AbstractConfig
    {
        if ($configClass !== RateLimitConfig::class) {
            throw new UnknownConfigException($configClass, self::class);
        }

        return new RateLimitConfig();
    }

    /**
     * Создает экземпляр хранилища на основе драйвера.
     */
    private function createStorage(): StorageInterface
    {
        switch ($this->driver) {
            case 'file':
                $storagePath = $this->storagePath ?: storage_path('ratelimit');
                return new FileBasedStorage($storagePath);

            case 'array':
                return new ArrayStorage();

            case 'redis':
                // TODO: Реализовать RedisStorage в будущем
                throw new \RuntimeException('Redis driver not yet implemented');

            default:
                throw new \RuntimeException("Unknown storage driver: {$this->driver}");
        }
    }
}
