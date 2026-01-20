<?php

namespace Vasoft\Joke\Core\Collections;

/**
 * Коллекция для хранения и управления произвольными данными в виде ключ-значение.
 *
 * Поддерживает значения любого скалярного типа, массивы и null.
 */
class PropsCollection
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
     * Устанавливает значение свойства.
     *
     * @param string $key Имя свойства
     * @param mixed $value Значение для сохранения (скаляр, массив или null)
     * @return static
     */
    public function set(string $key, mixed $value): static
    {
        $this->props[$key] = $value;
        return $this;
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

    /**
     * Заменяет все текущие свойства новым набором.
     *
     * @param array<string, mixed> $props Новый набор свойств
     * @return static
     */
    public function reset(array $props): static
    {
        $this->props = $props;
        return $this;
    }

    /**
     * Удаляет свойство по ключу.
     * @param string $key Имя удаляемого свойства
     * @return static
     */
    public function unset(string $key): static
    {
        if (array_key_exists($key, $this->props)) {
            unset($this->props[$key]);
        }
        return $this;
    }
}