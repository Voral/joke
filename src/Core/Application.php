<?php

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Contract\Core\Routing\RouterInterface;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Request\Request;
use Vasoft\Joke\Core\Response\HtmlResponse;
use Vasoft\Joke\Core\Response\JsonResponse;
use Vasoft\Joke\Core\Response\Response;

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
        $this->serviceContainer->registerSingleton(HttpRequest::class, $request);
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

    private function loadRoutes(): RouterInterface
    {
        /** @var RouterInterface $router */
        $router = $this->serviceContainer->get(RouterInterface::class);
        $file = $this->getFullPath($this->routeConfigWeb);
        if (file_exists($file)) {
            require $file;
        }
        return $router;
    }
}