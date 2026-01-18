<?php

namespace Vasoft\Joke\Tests\Fixtures\Controllers;

use Vasoft\Joke\Core\ServiceContainer;

class SingleController
{
    private array $data = [
        'apple',
        'banana',
        'orange',
        'strawberry'
    ];

    public function __construct(ServiceContainer $service) { }

    public function find(string $filter): array
    {
        return array_filter($this->data, fn($item) => str_contains($item, $filter));
    }

    public static function info(): array
    {
        return ['shopVersion' => '1.0.0'];
    }

    public function index(): array
    {
        return $this->data;
    }
}