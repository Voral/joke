<?php

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;
use Vasoft\Joke\Contract\Core\Routing\RouteInterface;
use Vasoft\Joke\Contract\Core\Routing\RouterInterface;
use Vasoft\Joke\Core\Middlewares\CsrfMiddleware;
use Vasoft\Joke\Core\Middlewares\ExceptionMiddleware;
use Vasoft\Joke\Core\Middlewares\Exceptions\WrongMiddlewareException;
use Vasoft\Joke\Core\Middlewares\MiddlewareCollection;
use Vasoft\Joke\Core\Middlewares\SessionMiddleware;
use Vasoft\Joke\Core\Middlewares\StdMiddleware;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Request\Request;
use Vasoft\Joke\Core\Response\HtmlResponse;
use Vasoft\Joke\Core\Response\JsonResponse;
use Vasoft\Joke\Core\Response\Response;
use Vasoft\Joke\Core\Routing\Exceptions\NotFoundException;
use Vasoft\Joke\Core\Routing\StdGroup;

class Application
{
    /**
     * @var MiddlewareCollection Массив глобальных middleware работают до определения маршрута
     */
    protected MiddlewareCollection $middlewares {
        get => $this->middlewares;
    }
    /**
     * @var MiddlewareCollection Массив middleware маршрут работают до определения маршрута
     */
    protected MiddlewareCollection $routeMiddlewares {
        get => $this->routeMiddlewares;
    }

    /**
     * @param string $basePath Базовый путь приложения
     * @param string $routeConfigWeb Путь к файлу конфигурации веб-маршрутов относительно базового пути
     * @param ServiceContainer $serviceContainer Объект DI контейнера
     */
    public function __construct(
        public readonly string $basePath,
        public readonly string $routeConfigWeb,
        public readonly ServiceContainer $serviceContainer,
    ) {
        $this->middlewares = new MiddlewareCollection()
            ->addMiddleware(ExceptionMiddleware::class, StdMiddleware::EXCEPTION->value);
        $this->routeMiddlewares = new MiddlewareCollection()
            ->addMiddleware(SessionMiddleware::class, StdMiddleware::SESSION->value)
            ->addMiddleware(CsrfMiddleware::class, StdMiddleware::CSRF->value, [StdGroup::WEB->value]);
        $this->loadRoutes();
    }

    /**
     * Добавляет глобальный middleware в коллекцию
     * Если middleware именованный производится поиск, и, если найден, производится замена middleware в той же позиции где
     * и был найден
     * @param MiddlewareInterface|string $middleware Экземпляр или класс middleware
     * @param string $name Наименование middleware для тех, которые могут быть только в единственном экземпляре
     * @return $this
     */
    public function addMiddleware(MiddlewareInterface|string $middleware, string $name = ''): static
    {
        $this->middlewares->addMiddleware($middleware, $name);
        return $this;
    }

    /**
     * Добавляет middleware в коллекцию middleware маршрутов (которые выполняются когда маршрут уже определен)
     * Если middleware именованный производится поиск, и, если найден, производится замена middleware в той же позиции где
     * и был найден. Возможна привязка к группе
     * @param MiddlewareInterface|string $middleware Экземпляр или класс middleware
     * @param string $name Наименование middleware для тех, которые могут быть только в единственном экземпляре
     * @param array<string> $groups Привязка middleware к набору групп.
     * @return $this
     */
    public function addRouteMiddleware(
        MiddlewareInterface|string $middleware,
        string $name = '',
        array $groups = []
    ): static {
        $this->routeMiddlewares->addMiddleware($middleware, $name, $groups);
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
        $response = $this->processMiddlewares($request, $this->middlewares->getArrayForRun(), $next);
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

    private function handleRoute(HttpRequest $request): mixed
    {
        $this->serviceContainer->registerSingleton(HttpRequest::class, $request);
        /** @var RouteInterface $route */
        $route = $this->serviceContainer->get(RouterInterface::class)?->findRoute($request);
        if ($route === null) {
            throw new NotFoundException('Route not found');
        }
        $next = static function () use ($request, $route) {
            return $route->run($request);
        };
        $middlewareCollection = $this->routeMiddlewares->withMiddlewares($route->getMiddlewares());

        $middlewares = $middlewareCollection->getArrayForRun($route->getGroups());
        return $this->processMiddlewares($request, $middlewares, $next);
    }

    /**
     * @param HttpRequest $request Входящий запрос
     * @param array<MiddlewareInterface|string> $middlewares Список middleware для выполнения
     * @param callable $next Следующий функция для выполнения
     * @return mixed
     * @throws WrongMiddlewareException
     */
    private function processMiddlewares(
        HttpRequest $request,
        array $middlewares,
        callable $next
    ): mixed {
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

    private function loadRoutes(): void
    {
        /** @var RouterInterface $router */
        $router = $this->serviceContainer->get(RouterInterface::class);
        $router->addAutoGroups([StdGroup::WEB->value]);
        $file = $this->getFullPath($this->routeConfigWeb);
        if (file_exists($file)) {
            require $file;
        }
        $router->cleanAutoGroups();
    }
}