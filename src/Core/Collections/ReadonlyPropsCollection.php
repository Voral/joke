<?php

namespace Vasoft\Joke\Core\Collections;

/**
 * Коллекция для хранения данными в виде ключ-значение. Только для чтения.
 *
 * Поддерживает значения любого скалярного типа, массивы и null.
 */
class ReadonlyPropsCollection
{
    /**
     * @param array<string,mixed> $props Начальный набор свойств
     */
    public function __construct(protected array $props) { }

    /**
     * Возвращает значение свойства по ключу.
     *
     * @param string $key Имя свойства
     * @param mixed $default Значение по умолчанию, если ключ не существует
     * @return null|int|float|string|bool|array Значение свойства или значение по умолчанию
     */
    public function get(string $key, mixed $default = null): null|int|float|string|bool|array
    {
        return $this->props[$key] ?? $default;
    }

    /**
     * Возвращает все свойства в виде ассоциативного массива.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->props;
    }
}