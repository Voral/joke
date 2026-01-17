<?php

namespace Vasoft\Joke\Contract\Core\Routing;

use Vasoft\Joke\Core\Request\HttpMethod;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Routing\Exceptions\NotFoundException;

/**
 * RouterInterface управляет HTTP-маршрутами и направляет входящие запросы соответствующим обработчикам.
 *
 * Класс должен поддерживать именованные маршруты, несколько HTTP-методов для одного маршрута
 */
interface RouterInterface
{
    /**
     * Регистрация маршрута отвечающего на POST запрос
     *
     * @param string $path Паттерн URI (например, '/users').
     * @param callable $callback Метод выполняющийся для данного маршрута
     * @param string $name Опциональное имя маршрута
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function post(string $path, callable $callback, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на GET запрос
     *
     * @param string $path Паттерн URI (например, '/users').
     * @param callable $callback Метод выполняющийся для данного маршрута
     * @param string $name Опциональное имя маршрута
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function get(string $path, callable $callback, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на PUT запрос
     *
     * @param string $path Паттерн URI (например, '/users').
     * @param callable $callback Метод выполняющийся для данного маршрута
     * @param string $name Опциональное имя маршрута
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function put(string $path, callable $callback, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на DELETE запрос
     *
     * @param string $path Паттерн URI (например, '/users').
     * @param callable $callback Метод выполняющийся для данного маршрута
     * @param string $name Опциональное имя маршрута
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function delete(string $path, callable $callback, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на PATH запрос
     *
     * @param string $path Паттерн URI (например, '/users').
     * @param callable $callback Метод выполняющийся для данного маршрута
     * @param string $name Опциональное имя маршрута
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function patch(string $path, callable $callback, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на PATH запрос
     *
     * @param string $path Паттерн URI (например, '/users').
     * @param callable $callback Метод выполняющийся для данного маршрута
     * @param string $name Опциональное имя маршрута
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function head(string $path, callable $callback, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на любой запрос
     *
     * @param string $path Паттерн URI (например, '/users').
     * @param callable $callback Метод выполняющийся для данного маршрута
     * @param string $name Опциональное имя маршрута
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function any(string $path, callable $callback, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на запрос заданного перечня методов
     *
     * @param list<HttpMethod> $methods Список допустимых методов
     * @param string $path Паттерн URI (например, '/users').
     * @param callable $callback Метод выполняющийся для данного маршрута
     * @param string $name Опциональное имя маршрута
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function match(array $methods, string $path, callable $callback, string $name = ''): RouteInterface;

    /**
     * Направляет входящий HTTP-запрос обработчику соответствующего маршрута.
     *
     * @param HttpRequest $request Входящий запрос.
     * @return mixed Результат возвращаемый обработчиком маршрута
     * @throws NotFoundException Выбрасывается в случае если подходящий маршрут не найден
     */
    public function dispatch(HttpRequest $request): mixed;

    /**
     * Поиск маршрута для входящего запроса
     *
     * @param HttpRequest $request Входящий запрос
     * @return RouteInterface|null Возвращает объект маршрута или null, если подходящий маршрут не найден
     */
    public function findRoute(HttpRequest $request): ?RouteInterface;

    /**
     * Возвращает маршрут по имени
     *
     * @param string $name Имя маршрута
     * @return RouteInterface|null Возвращает объект маршрута или null, если маршрут не найден
     */
    public function route(string $name): ?RouteInterface;
}