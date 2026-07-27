<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimit;

use Vasoft\Joke\Config\AbstractConfig;

/**
 * Конфигурация для Rate Limiting.
 *
 * Поддерживает именованные профили лимитов (default, auth и т.д.).
 */
final class RateLimitConfig extends AbstractConfig
{
    /**
     * Включен ли rate limiting.
     *
     * @var bool
     */
    public bool $enabled {
        get => $this->enabled;
    }

    /**
     * Директория для хранилища счётчиков.
     *
     * @var string
     */
    public string $storagePath {
        get => $this->storagePath;
    }

    /**
     * Добавлять ли HTTP заголовки X-RateLimit-* в ответ.
     *
     * @var bool
     */
    public bool $withHeaders {
        get => $this->withHeaders;
    }

    /**
     * Именованные профили лимитов.
     * Структура: ['profile_name' => ['limit' => int, 'window' => int]]
     *
     * @var array<string, array{limit: int, window: int}>
     */
    public array $profiles {
        get => $this->profiles;
    }

    public function __construct()
    {
        $this->enabled = false;
        $this->storagePath = sys_get_temp_dir() . '/joke_ratelimit';
        $this->withHeaders = true;
        $this->profiles = [
            'default' => ['limit' => 100, 'window' => 3600],
        ];
    }

    /**
     * Устанавливает, включен ли rate limiting.
     */
    public function setEnabled(bool $enabled): static
    {
        $this->guard();
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Устанавливает директорию для хранилища.
     */
    public function setStoragePath(string $path): static
    {
        $this->guard();
        $this->storagePath = $path;

        return $this;
    }

    /**
     * Устанавливает, добавлять ли HTTP заголовки.
     */
    public function setWithHeaders(bool $withHeaders): static
    {
        $this->guard();
        $this->withHeaders = $withHeaders;

        return $this;
    }

    /**
     * Регистрирует именованный профиль лимитов.
     *
     * @param string $name Имя профиля (e.g. "auth", "api", "default")
     * @param int $limit Максимум запросов
     * @param int $window Окно в секундах
     */
    public function setProfile(string $name, int $limit, int $window): static
    {
        $this->guard();
        $this->profiles[$name] = ['limit' => $limit, 'window' => $window];

        return $this;
    }

    /**
     * Получает профиль лимитов по имени.
     *
     * @param string $name Имя профиля
     * @return array{limit: int, window: int}|null Профиль или null если не найден
     */
    public function profile(string $name): ?array
    {
        return $this->profiles[$name] ?? null;
    }
}
