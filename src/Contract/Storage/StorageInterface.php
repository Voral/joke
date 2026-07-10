<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Storage;

/**
 * Универсальный интерфейс хранилища ключ-значение с поддержкой TTL.
 *
 * Это фундамент для слоя хранения (Storage Layer) в фреймворке Joke.
 * Реализации: FileDriver, ArrayDriver (для тестов), RedisDriver, SQLiteDriver.
 *
 * Все методы ДОЛЖНЫ быть безопасны для конкурентного доступа из нескольких процессов.
 */
interface StorageInterface
{
    /**
     * Возвращает значение по ключу.
     *
     * @param string $key Уникальный идентификатор
     *
     * @return null|string null, если ключ не найден или истёк срок его жизни
     */
    public function get(string $key): ?string;

    /**
     * Сохраняет значение с опциональным временем жизни.
     *
     * @param string   $key   Уникальный идентификатор
     * @param string   $value Сохраняемое значение
     * @param null|int $ttl   Время жизни в секундах. null = бессрочно
     *
     * @return bool true в случае успеха
     */
    public function set(string $key, string $value, ?int $ttl = null): bool;

    /**
     * Удаляет ключ.
     *
     * @param string $key Уникальный идентификатор
     *
     * @return bool true, если ключ существовал и был удалён
     */
    public function delete(string $key): bool;

    /**
     * Проверяет существование ключа и то, что срок его жизни не истёк.
     *
     * @param string $key Уникальный идентификатор
     */
    public function exists(string $key): bool;

    /**
     * Атомарно увеличивает числовое значение и возвращает новое значение.
     *
     * Если ключ не существует, он создаётся со значением 1.
     * TTL применяется ТОЛЬКО при создании ключа.
     *
     * @param string $key Уникальный идентификатор
     * @param int    $ttl Время жизни в секундах (применяется при создании)
     *
     * @return int Новое значение после инкремента
     */
    public function increment(string $key, int $ttl): int;

    /**
     * Удаляет все истёкшие записи.
     * Опционально — реализации могут выполнять очистку также при чтении.
     *
     * @return int Количество очищенных записей
     */
    public function cleanup(): int;
}