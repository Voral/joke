<?php

declare(strict_types=1);

namespace Vasoft\Joke\Collections;

/**
 * Коллекция HTTP-заголовков.
 *
 * Расширяет PropsCollection,
 * Специализированный метод для работы с Content-Type.
 */
class HeadersCollection extends PropsCollection
{
    /**
     * Возвращает значение заголовка Content-Type.
     */
    public ?string $contentType {
        get => $this->props['Content-Type'] ?? null;
    }

    /**
     * Устанавливает значение заголовка Content-Type.
     *
     * @param string $value MIME-тип, например: 'text/html', 'application/json'
     */
    public function setContentType(string $value): static
    {
        $this->props['Content-Type'] = $value;

        return $this;
    }
}
