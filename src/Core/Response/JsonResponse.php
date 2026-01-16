<?php

namespace Vasoft\Joke\Core\Response;

use JsonException;

class JsonResponse extends Response
{
    protected array $body = [];

    public function __construct()
    {
        parent::__construct();
        $this->headers->setContentType('application/json');
    }

    public function setBody($body): static
    {
        $this->body = $body;
        return $this;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @return string
     * @throws JsonException
     */
    public function getBodyAsString(): string
    {
        return json_encode($this->body, JSON_THROW_ON_ERROR);
    }
}