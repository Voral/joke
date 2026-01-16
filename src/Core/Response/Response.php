<?php

namespace Vasoft\Joke\Core\Response;

use Vasoft\Joke\Core\Collections\HeadersCollection;

abstract class Response
{
    public ResponseStatus $status {
        get => $this->status ??= ResponseStatus::OK;

        set(ResponseStatus $status) {
            $this->status = $status;
        }
    }
    public ?HeadersCollection $headers = null {
        get {
            return $this->headers ??= new HeadersCollection([]);
        }
    }

    public function __construct()
    {
    }

    abstract public function setBody($body): static;

    abstract public function getBody(): mixed;

    public function send(): static
    {
        $this->sendHeaders();
        echo $this->getBodyAsString();
        return $this;
    }

    abstract public function getBodyAsString(): string;

    protected function sendHeaders(): void
    {
        $headers = $this->headers->getAll();
        foreach ($headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
    }
}