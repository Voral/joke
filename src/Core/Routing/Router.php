<?php

namespace Vasoft\Joke\Core\Routing;

use Vasoft\Joke\Contract\Core\Routing\RouterInterface;
use Vasoft\Joke\Core\Request\HttpMethod;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Routing\Exceptions\NotFoundException;
use Vasoft\Joke\Core\ServiceContainer;

/**
 *  Управляет HTTP-маршрутами и направляет входящие запросы соответствующим обработчикам.
 *
 *  Класс должен поддерживать именованные маршруты, несколько HTTP-методов для одного маршрута
 *  и использует ServiceContainer для разрешения и вызова коллбэков контроллеров.
 *  Маршруты хранятся с индексацией по методу и имени для эффективного сопоставления
 *  и генерации URL.
 *
 * @todo Продумать вопрос аналога маршрутов для консоли. Возможно стоит отойти от HttpMethod на string для универсальности
 */
class Router implements RouterInterface
{
    /**
     * Хранилище роутов сгруппированных по HTTP методу (например, 'GET', 'POST').
     *
     * Структура: ['GET' => [Route, Route, ...], 'POST' => [...], ...]
     * @var array<string, list<Route>>
     */
    protected array $routes = [];
    /**
     * Хранилище именованных роутов для легкого доступа по имени
     *
     * @var array<string, Route>
     */
    protected array $namedRoutes = [];

    /**
     * @param ServiceContainer $serviceContainer DI-контейнер, используемый для разрешения зависимостей маршрутов.
     */
    public function __construct(protected readonly ServiceContainer $serviceContainer) { }

    public function post(string $path, callable $callback, string $name = ''): Route
    {
        return $this->match([HttpMethod::POST], $path, $callback, $name);
    }


    public function get(string $path, callable $callback, string $name = ''): Route
    {
        return $this->match([HttpMethod::GET], $path, $callback, $name);
    }

    public function put(string $path, callable $callback, string $name = ''): Route
    {
        return $this->match([HttpMethod::PUT], $path, $callback, $name);
    }

    public function delete(string $path, callable $callback, string $name = ''): Route
    {
        return $this->match([HttpMethod::DELETE], $path, $callback, $name);
    }

    public function patch(string $path, callable $callback, string $name = ''): Route
    {
        return $this->match([HttpMethod::PATCH], $path, $callback, $name);
    }


    public function head(string $path, callable $callback, string $name = ''): Route
    {
        return $this->match([HttpMethod::HEAD], $path, $callback, $name);
    }

    public function any(string $path, callable $callback, string $name = ''): Route
    {
        return $this->match(HttpMethod::cases(), $path, $callback, $name);
    }

    public function match(array $methods, string $path, callable $callback, string $name = ''): Route
    {
        if ($name === '') {
            $name = $this->getRouteIndex($methods, $path);
        }
        $this->namedRoutes[$name] = new Route($this->serviceContainer, $path, $methods[0], $callback);
        foreach ($methods as $method) {
            $this->routes[$method->value][] = &$this->namedRoutes[$name];
        }
        return $this->namedRoutes[$name];
    }

    /**
     * Генерация имени для безымянного маршрута
     *
     * Формат: "method1#method2|/path"
     *
     * @param list<HttpMethod> $methods Список метод обрабатываемых маршрутом
     * @param string $path Паттерн URI (например, '/users').
     * @return string Имя маршрута
     */
    private function getRouteIndex(array $methods, string $path): string
    {
        $parts = array_map(fn(HttpMethod $part) => strtolower($part->value), $methods);
        return implode('#', $parts) . '|' . $path;
    }

    /**
     * @inherit
     */
    public function dispatch(HttpRequest $request): mixed
    {
        $route = $this->findRoute($request);
        if ($route === null) {
            throw new NotFoundException('Route not found');
        }
        return $route->run($request);
    }

    public function findRoute(HttpRequest $request): ?Route
    {
        $method = $request->method;
        if (!isset($this->routes[$method->value])) {
            return null;
        }
        foreach ($this->routes[$method->value] as $route) {
            if ($route->matches($request)) {
                return $method !== $route->method ? $route->withMethod($method) : $route;
            }
        }
        return null;
    }

    public function route(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }
}