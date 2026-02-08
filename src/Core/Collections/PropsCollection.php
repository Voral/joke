<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Collections;

/**
 * Коллекция для хранения и управления произвольными данными в виде ключ-значение.
 *
 * Поддерживает значения любого скалярного типа, массивы и null.
 */
class PropsCollection extends ReadonlyPropsCollection
{
    /**
     * Устанавливает значение свойства.
     *
     * @param string $key   Имя свойства
     * @param mixed  $value Значение для сохранения (скаляр, массив или null)
     */
    public function set(string $key, mixed $value): static
    {
        $this->props[$key] = $value;

        return $this;
    }

    /**
     * Заменяет все текущие свойства новым набором.
     *
     * @param array<string, mixed> $props Новый набор свойств
     */
    public function reset(array $props): static
    {
        $this->props = $props;

        return $this;
    }

    /**
     * Удаляет свойство по ключу.
     *
     * @param string $key Имя удаляемого свойства
     */
    public function unset(string $key): static
    {
        if (array_key_exists($key, $this->props)) {
            unset($this->props[$key]);
        }

        return $this;
    }
}
