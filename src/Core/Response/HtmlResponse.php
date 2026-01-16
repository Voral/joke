<?php

namespace Vasoft\Joke\Core\Response;

class HtmlResponse extends Response
{
    protected string $body = '';

    public function __construct()
    {
        parent::__construct();
        $this->headers->setContentType('text/html');
    }

    public function setBody(mixed $body): static
    {
        $this->body = (string)$body;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getBodyAsString(): string
    {
        return $this->body;
    }
}