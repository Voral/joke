<?php

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;
use Vasoft\Joke\Contract\Core\Routing\RouterInterface;
use Vasoft\Joke\Core\Middlewares\ExceptionMiddleware;
use Vasoft\Joke\Core\Middlewares\Exceptions\WrongMiddlewareException;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Request\Request;
use Vasoft\Joke\Core\Response\HtmlResponse;
use Vasoft\Joke\Core\Response\JsonResponse;
use Vasoft\Joke\Core\Response\Response;
use Vasoft\Joke\Core\Routing\Exceptions\NotFoundException;

class Application
{
    /**
     * @var array<MiddlewareInterface|string> Массив глобальных middleware
     */
    protected array $middlewares = [
        ExceptionMiddleware::class,
    ];

    public function __construct(
        public readonly string $basePath,
        public readonly string $routeConfigWeb,
        public readonly ServiceContainer $serviceContainer,
    ) {
    }

    public function addMiddleware(MiddlewareInterface|string $middleware): static
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    private function getFullPath(string $relativePath): string
    {
        return realpath(
            rtrim($this->basePath, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            ltrim($relativePath, DIRECTORY_SEPARATOR)
        );
    }

    /**
     * @param Request $request Входящий запрос
     * @return void
     * @throws NotFoundException Выбрасывается если маршрут не найден
     * @throws WrongMiddlewareException Выбрасывается при некорректном классе middleware
     */
    public function handle(Request $request): void
    {
        $next = function () use ($request) {
            return $this->handleRoute($request);
        };
        $response = $this->processMiddlewares($request, $this->middlewares, $next);
        $this->sendResponse($response);
    }

    private function sendResponse(mixed $response): void
    {
        if (!($response instanceof Response)) {
            if (is_array($response)) {
                $response = new JsonResponse()->setBody($response);
            } else {
                $response = new HtmlResponse()->setBody($response);
            }
        }
        $response->send();
    }

    private function handleRoute(Request $request): mixed
    {
        $this->serviceContainer->registerSingleton(HttpRequest::class, $request);
        $route = $this->loadRoutes()->findRoute($request);
        if ($route === null) {
            throw new NotFoundException('Route not found');
        }
        // @todo Миддлвары групп и отдельных маршрутов
        $middlewares = [];
        $next = static function () use ($request, $route) {
            return $route->run($request);
        };
        
        return $this->processMiddlewares($request, $middlewares, $next);
    }

    private function processMiddlewares(HttpRequest $request, array $middlewares, callable $next): mixed
    {
        $middlewares = array_reverse($middlewares);
        foreach ($middlewares as $middleware) {
            $next = function () use ($middleware, $next, $request) {
                $instance = ($middleware instanceof MiddlewareInterface)
                    ? $middleware
                    : $this->resolveMiddleware($middleware);
                if ($instance === null) {
                    throw new WrongMiddlewareException($middleware);
                }
                return $instance->handle(
                    $request,
                    $next
                );
            };
        }
        return $next();
    }

    private function resolveMiddleware(string $middleware): ?MiddlewareInterface
    {
        $resolver = $this->serviceContainer->getParameterResolver();
        $args = $resolver->resolveForConstructor($middleware);
        $instance = new $middleware(...$args);
        return $instance instanceof MiddlewareInterface ? $instance : null;
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