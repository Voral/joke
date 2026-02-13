<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Http\Response;

use Vasoft\Joke\Http\Response\BinaryResponse;

class DummyFileResponse extends BinaryResponse
{
    public array $sentHeaders = [];

    public function send(): static
    {
        $this->sentHeaders = [];

        return parent::send();
    }

    protected function sendHeaders(): void
    {
        $this->sentHeaders = $this->headers->getAll();
    }

    public function getContentType(): string
    {
        return 'application/test';
    }
}
