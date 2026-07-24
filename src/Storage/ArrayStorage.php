<?php

declare(strict_types=1);

namespace Vasoft\Joke\Storage;

use Vasoft\Joke\Contract\Storage\StorageInterface;

/**
 * Массивное хранилище для тестов и кэширования в памяти.
 *
 * Не поддерживает ttl ( использует PHP-память, которая очищается после запроса).
 */
class ArrayStorage implements StorageInterface
{
    /**
     * @var array<string, array{value: string, version: int}>
     */
    private array $storage = [];

    /**
     * {@inheritDoc}
     */
    public function get(string $key): ?string
    {
        return $this->storage[$key]['value'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, string $value, int $ttl = 0): bool
    {
        $this->storage[$key] = [
            'value' => $value,
            'version' => 0,
        ];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        if (!isset($this->storage[$key])) {
            return true;
        }

        unset($this->storage[$key]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function cas(
        string $key,
        callable $callback,
        int $ttl = 0,
        int $maxRetries = 10
    ): array {
        $currentValue = $this->storage[$key]['value'] ?? null;
        $currentVersion = $this->storage[$key]['version'] ?? 0;

        $newValue = $callback($currentValue);

        if ($newValue === null) {
            unset($this->storage[$key]);
            return ['value' => '', 'version' => $currentVersion + 1];
        }

        $this->storage[$key] = [
            'value' => $newValue,
            'version' => $currentVersion + 1,
        ];

        return [
            'value' => $newValue,
            'version' => $currentVersion + 1,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $this->storage = [];

        return true;
    }

    /**
     * Возвращает все данные хранилища (для тестов и отладки).
     *
     * @return array<string, array{value: string, version: int}>
     */
    public function getAll(): array
    {
        return $this->storage;
    }
}
