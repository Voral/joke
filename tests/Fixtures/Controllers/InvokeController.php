<?php

namespace Vasoft\Joke\Tests\Fixtures\Controllers;


use Vasoft\Joke\Core\ServiceContainer;

readonly class InvokeController
{
    public function __construct(private ServiceContainer $serviceContainer) { }

    public function __invoke(string $prop): array
    {
        return [
            'ServiceContainer' => spl_object_id($this->serviceContainer),
            'propValue' => $prop,
        ];
    }
}