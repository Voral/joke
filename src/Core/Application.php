<?php

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Core\Request\Request;
use Vasoft\Joke\Core\Response\HtmlResponse;
use Vasoft\Joke\Core\Response\JsonResponse;
use Vasoft\Joke\Core\Response\Response;
use Vasoft\Joke\Core\Routing\Router;

readonly class Application
{
    public function __construct(
        public string $basePath,
        public string $routeConfigWeb,
        public ServiceContainer $serviceContainer,
    ) {
    }

    private function getFullPath(string $relativePath): string
    {
        return realpath(
            rtrim($this->basePath, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            ltrim($relativePath, DIRECTORY_SEPARATOR)
        );
    }

    public function handle(Request $request): void
    {
        $response = $this->loadRoutes()->dispatch($request);
        if (!($response instanceof Response)) {
            if (is_array($response)) {
                $response = new JsonResponse()->setBody($response);
            } else {
                $response = new HtmlResponse()->setBody($response);
            }
        }
        $response->send();
    }

    private function loadRoutes(): Router
    {
        /** @var Router $router */
        $router = $this->serviceContainer->get(Router::class);
        $file = $this->getFullPath($this->routeConfigWeb);
        if (file_exists($file)) {
            require $file;
        }
        return $router;
    }
}