<?php

namespace Vasoft\Joke\Core\Routing;

use Vasoft\Joke\Core\Request\HttpMethod;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Routing\Exceptions\NotFoundException;

class Router
{
    protected array $routes = [];
    protected array $namedRoutes = [];

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

    /**
     * @param array<HttpMethod> $methods
     * @param string $path
     * @param callable $callback
     * @param string $name
     * @return Route
     */
    public function match(array $methods, string $path, callable $callback, string $name = ''): Route
    {
        if ($name === '') {
            $name = $this->getRouteIndex($methods, $path);
        }
        $this->namedRoutes[$name] = new Route($path, $methods[0], $callback);
        foreach ($methods as $method) {
            $this->routes[$method->value][] = &$this->namedRoutes[$name];
        }
        return $this->namedRoutes[$name];
    }

    /**
     * @param array<HttpMethod> $methods
     * @param string $path
     * @return string
     */
    private function getRouteIndex(array $methods, string $path): string
    {
        $parts = array_map(fn(HttpMethod $part) => strtolower($part->value), $methods);
        return implode('#', $parts) . '|' . $path;
    }

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