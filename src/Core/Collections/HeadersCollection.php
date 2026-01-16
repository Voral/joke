<?php

namespace Vasoft\Joke\Core\Collections;

class HeadersCollection extends PropsCollection
{
    public ?string $contentType {
        get => $this->props['Content-Type'] ?? null;
    }

    public function setContentType(string $value): static
    {
        $this->props['Content-Type'] = $value;
        return $this;
    }
}