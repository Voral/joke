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
     * @var null|string Путь к хранилищу
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

    public function __construct(
        private readonly ServiceContainer $serviceContainer,
    ) {}

    /**
     * Устанавливает путь к хранилищу.
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
     */
    public function setDriver(string $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function register(): void
    {
        $container = $this->serviceContainer;
        $container->registerSingleton(StorageInterface::class, fn(ServiceContainer $container) => $this->createStorage());

        $container->registerSingleton(RateLimiterInterface::class, static function (ServiceContainer $container) {
            $storage = $container->get(StorageInterface::class);

            return new SlidingWindowRateLimiter($storage);
        });

        $container->registerSingleton(ClientIdentifierInterface::class, fn(ServiceContainer $container) => new $this->clientIdentifierClass());
    }

    public function boot(): void
    {
        // Провайдер не требует дополнительной инициализации
    }

    public function provides(): array
    {
        return [
            StorageInterface::class,
            RateLimiterInterface::class,
            ClientIdentifierInterface::class,
        ];
    }

    public static function provideConfigs(): array
    {
        return [RateLimitConfig::class];
    }

    public static function buildConfig(string $configClass, ServiceContainer $container): AbstractConfig
    {
        if (RateLimitConfig::class !== $configClass) {
            throw new UnknownConfigException($configClass);
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
