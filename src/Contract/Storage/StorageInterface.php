<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Storage;

/**
 * Интерфейс универсального хранилища данных.
 *
 * Предоставляет атомарную операцию получения и установки значений с TTL.
 * Основан на паттерне "cas" (compare-and-swap), что позволяет реализовать
 * атомарные операции инкремента для Rate Limiting.
 *
 * Используется как базовый слой для:
 * - Rate Limiting
 * - Кэширования
 * - Сессий
 * - Блокировок
 */
interface StorageInterface
{
    /**
     * Получить значение по ключу.
     *
     * @param string $key Уникальный идентификатор записи
     *
     * @return string|null Возвращаемое значение или null, если ключ не найден
     */
    public function get(string $key): ?string;

    /**
     * Установить значение по ключу с TTL.
     *
     * @param string $key   Ключ записи
     * @param string $value Значение для записи
     * @param int    $ttl   Время жизни в секундах (0 = без ограничения)
     *
     * @return bool true при успешной записи
     */
    public function set(string $key, string $value, int $ttl = 0): bool;

    /**
     * Удалить значение по ключу.
     *
     * @param string $key Ключ для удаления
     *
     * @return bool true, если ключ был удален или не существовал
     */
    public function delete(string $key): bool;

    /**
     * Атомарная операция compare-and-swap (CAS).
     *
     * Позволяет безопасно обновлять значения в многопроцессной среде.
     * Ожидает, что значение будет в формате JSON с полями:
     * - value: текущее значение
     * - version: версия для CAS
     *
     * @param string   $key          Ключ записи
     * @param callable $callback     Функция, которая принимает старое значение и возвращает новое
     *                               Функция получает null, если ключ не существует
     * @param int      $ttl          Время жизни нового значения (0 = без ограничения)
     * @param int      $maxRetries   Максимальное число попыток при конфликте
     *
     * @return array{value: string, version: int} Новое значение и версия
     *
     * @throws StorageException Если превышено число попыток
     */
    public function cas(
        string $key,
        callable $callback,
        int $ttl = 0,
        int $maxRetries = 10
    ): array;

    /**
     * Очистить хранилище (удалить все записи).
     *
     * @return bool true при успешной очистке
     */
    public function clear(): bool;
}
