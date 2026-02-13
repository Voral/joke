<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Controllers;

use Vasoft\Joke\Container\Exceptions\AutowiredException;
use Vasoft\Joke\Container\ServiceContainer;

class SingleController
{
    private array $data = [
        'apple',
        'banana',
        'orange',
        'strawberry',
    ];

    public function __construct(ServiceContainer $service) {}

    public function find(string $filter): array
    {
        return array_filter($this->data, static fn($item) => str_contains($item, $filter));
    }

    public static function info(): array
    {
        throw new AutowiredException('test', 'todo');

        return ['shopVersion' => '1.0.0'];
    }

    public function index(): array
    {
        return $this->data;
    }
}
