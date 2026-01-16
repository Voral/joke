<?php

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Core\Request\Request;

class Application
{
    public function __construct(
        public readonly string $basePath,
        public readonly string $routeConfigWeb
    ) {
    }

    public function run(Request $request): void
    {
        $routes = $this->loadRoutes();
    }

    private function loadRoutes(): array
    {
        if (file_exists($this->routeConfigWeb)) {
            return require $this->routeConfigWeb;
        }
        return [];
    }

    public function get(string $path, callable $callback): void
    {
    }

}