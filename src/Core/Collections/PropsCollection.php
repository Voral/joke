<?php

namespace Vasoft\Joke\Core\Collections;

class PropsCollection
{
    /**
     * @param array<string,mixed> $props
     */
    public function __construct(protected array $props) { }

    public function get(string $key, mixed $default = null): null|int|float|string|bool|array
    {
        return $this->props[$key] ?? $default;
    }

    public function set(string $key, mixed $value): static
    {
        $this->props[$key] = $value;
        return $this;
    }

    public function getAll(): array
    {
        return $this->props;
    }

    public function reset(array $props): static
    {
        $this->props = $props;
        return $this;
    }
    /**
     * Удаление переменной
     * @param string $key ключ переменной
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