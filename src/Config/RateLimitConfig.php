<?php

declare(strict_types=1);

namespace Vasoft\Joke\Config;

use Vasoft\Joke\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\Provider\RateLimiterServiceProvider;

/**
 * Конфигурация для Rate Limiter.
 */
class RateLimitConfig extends AbstractConfig
{
    /**
     * @var int Максимальное количество запросов по умолчанию
     */
    private int $defaultLimit = 100;

    /**
     * @var int Временное окно по умолчанию в секундах
     */
    private int $defaultWindow = 60;

    /**
     * @var string Драйвер хранилища (file|array|redis)
     */
    private string $driver = 'file';

    /**
     * @var string|null Путь к хранилищу
     */
    private ?string $storagePath = null;

    /**
     * @var class-string<ClientIdentifierInterface> Класс идентификатора клиентов
     */
    private string $clientIdentifierClass = \Vasoft\Joke\RateLimiter\DefaultClientIdentifier::class;

    /**
     * @var array<string, array{limit: int, window: int}> Гибкие лимиты для маршрутов
     */
    private array $routeLimits = [];

    /**
     * Устанавливает максимальное количество запросов по умолчанию.
     *
     * @param int $defaultLimit
     *
     * @return self
     */
    public function setDefaultLimit(int $defaultLimit): self
    {
        $this->guard();
        $this->defaultLimit = $defaultLimit;
        
        return $this;
    }

    /**
     * Возвращает максимальное количество запросов по умолчанию.
     */
    public function getDefaultLimit(): int
    {
        return $this->defaultLimit;
    }

    /**
     * Устанавливает временное окно по умолчанию в секундах.
     *
     * @param int $defaultWindow
     *
     * @return self
     */
    public function setDefaultWindow(int $defaultWindow): self
    {
        $this->guard();
        $this->defaultWindow = $defaultWindow;
        
        return $this;
    }

    /**
     * Возвращает временное окно по умолчанию в секундах.
     */
    public function getDefaultWindow(): int
    {
        return $this->defaultWindow;
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
        $this->guard();
        $this->driver = $driver;
        
        return $this;
    }

    /**
     * Возвращает драйвер хранилища.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Устанавливает путь к хранилищу.
     *
     * @param string $storagePath
     *
     * @return self
     */
    public function setStoragePath(string $storagePath): self
    {
        $this->guard();
        $this->storagePath = $storagePath;
        
        return $this;
    }

    /**
     * Возвращает путь к хранилищу.
     */
    public function getStoragePath(): ?string
    {
        return $this->storagePath;
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
        $this->guard();
        $this->clientIdentifierClass = $clientIdentifierClass;
        
        return $this;
    }

    /**
     * Возвращает класс идентификатора клиентов.
     */
    public function getClientIdentifierClass(): string
    {
        return $this->clientIdentifierClass;
    }

    /**
     * Устанавливает гибкий лимит для конкретного маршрута.
     *
     * @param string $routeName Имя маршрута или паттерн
     * @param int    $limit     Максимальное количество запросов
     * @param int    $window    Временное окно в секундах
     *
     * @return self
     */
    public function setRouteLimit(string $routeName, int $limit, int $window): self
    {
        $this->guard();
        $this->routeLimits[$routeName] = [
            'limit' => $limit,
            'window' => $window,
        ];
        
        return $this;
    }

    /**
     * Возвращает гибкие лимиты для маршрутов.
     *
     * @return array<string, array{limit: int, window: int}>
     */
    public function getRouteLimits(): array
    {
        return $this->routeLimits;
    }

    /**
     * Создает провайдер на основе конфигурации.
     */
    public function createProvider(): RateLimiterServiceProvider
    {
        $provider = new RateLimiterServiceProvider();
        
        if ($this->storagePath !== null) {
            $provider->setStoragePath($this->storagePath);
        }
        
        $provider->setClientIdentifierClass($this->clientIdentifierClass);
        
        return $provider;
    }
}
