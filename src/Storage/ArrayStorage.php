<?php

declare(strict_types=1);

namespace Vasoft\Joke\Storage;

use Vasoft\Joke\Contract\RateLimit\StorageInterface;

/**
 * In-memory реализация StorageInterface для тестирования.
 *
 * Хранит данные в PHP массиве, поддерживает TTL, позволяет "прокручивать" время.
 * Используется в PHPUnit тестах для изоляции и скорости.
 */
final class ArrayStorage implements StorageInterface
{
    /**
     * Структура: ['key' => ['value' => int, 'expiresAt' => int]]
     *
     * @var array<string, array{value: int, expiresAt: int}>
     */
    private array $data = [];

    /**
     * Текущее время (для тестов).
     * Позволяет "прокручивать" время без sleep().
     *
     * @var int Unix timestamp
     */
    private int $currentTime;

    public function __construct(?int $currentTime = null)
    {
        $this->currentTime = $currentTime ?? time();
    }

    public function increment(string $key, int $ttl = 3600): int
    {
        $this->removeExpiredKey($key);

        $newValue = ($this->data[$key]['value'] ?? 0) + 1;
        $this->data[$key] = [
            'value' => $newValue,
            'expiresAt' => $this->currentTime + $ttl,
        ];

        return $newValue;
    }

    public function get(string $key): int
    {
        $this->removeExpiredKey($key);

        return $this->data[$key]['value'] ?? 0;
    }

    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * "Прокручивает" текущее время на N секунд.
     *
     * Используется в тестах для проверки истечения TTL без sleep().
     *
     * @param int $seconds Количество секунд для продвижения
     */
    public function advanceTime(int $seconds): void
    {
        $this->currentTime += $seconds;
    }

    /**
     * Устанавливает текущее время явно.
     *
     * @param int $timestamp Unix timestamp
     */
    public function setCurrentTime(int $timestamp): void
    {
        $this->currentTime = $timestamp;
    }

    /**
     * Получает текущее время (для отладки).
     *
     * @return int Unix timestamp
     */
    public function getCurrentTime(): int
    {
        return $this->currentTime;
    }

    /**
     * Удаляет ключ, если его TTL истёк.
     */
    private function removeExpiredKey(string $key): void
    {
        if (isset($this->data[$key]) && $this->data[$key]['expiresAt'] <= $this->currentTime) {
            unset($this->data[$key]);
        }
    }
}
