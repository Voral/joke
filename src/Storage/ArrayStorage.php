<?php

declare(strict_types=1);

namespace Vasoft\Joke\Storage;

use Vasoft\Joke\Contract\Storage\StorageInterface;

/**
 * Хранилище ключ-значение в оперативной памяти.
 *
 * Предназначено для использования в тестах.
 * Не требует файловой системы, все операции атомарны в рамках одного процесса.
 * Поддерживает инъекцию времени для детерминированных тестов.
 *
 * @see StorageInterface
 */
class ArrayStorage implements StorageInterface
{
    /**
     * @var array<string, array{value: string, expires_at: ?int}>
     */
    private array $data = [];

    /**
     * @param int $time Текущее время (Unix-метка). Для тестов можно передать произвольное значение
     */
    public function __construct(
        private int $time = 0,
    ) {
        if (0 === $this->time) {
            $this->time = time();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): ?string
    {
        $entry = $this->data[$key] ?? null;
        if (null === $entry) {
            return null;
        }

        if (null !== $entry['expires_at'] && $entry['expires_at'] <= $this->time) {
            unset($this->data[$key]);

            return null;
        }

        return $entry['value'];
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, string $value, ?int $ttl = null): bool
    {
        $this->data[$key] = [
            'value' => $value,
            'expires_at' => null !== $ttl ? $this->time + $ttl : null,
        ];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $exists = isset($this->data[$key]);
        unset($this->data[$key]);

        return $exists;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $key): bool
    {
        return null !== $this->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $ttl): int
    {
        $current = (int) ($this->get($key) ?? '0');
        $newValue = $current + 1;

        if (0 === $current) {
            $this->data[$key] = [
                'value' => (string) $newValue,
                'expires_at' => $this->time + $ttl,
            ];
        } else {
            $this->data[$key]['value'] = (string) $newValue;
        }

        return $newValue;
    }

    /**
     * {@inheritDoc}
     */
    public function cleanup(): int
    {
        $count = 0;
        foreach ($this->data as $key => $entry) {
            if (null !== $entry['expires_at'] && $entry['expires_at'] <= $this->time) {
                unset($this->data[$key]);
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Устанавливает текущее время хранилища.
     * Используется в тестах для симуляции временных сценариев.
     *
     * @param int $time Unix-метка времени
     */
    public function setTime(int $time): void
    {
        $this->time = $time;
    }
}