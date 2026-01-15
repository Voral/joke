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

    public function getAll(): array
    {
        return $this->props;
    }

    public function reset(array $props): void
    {
        $this->props = $props;
    }
}