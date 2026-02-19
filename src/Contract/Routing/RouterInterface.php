<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Routing;

use Vasoft\Joke\Http\HttpMethod;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Routing\Exceptions\NotFoundException;

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
     * Создание нового ресурса или выполнение произвольного действия. Не идемпотентен. Меняет состояние
     *
     * @param string                                                     $path    паттерн URI (например, '/users')
     * @param array{class-string|object, non-empty-string}|object|string $handler Метод выполняющийся для данного маршрута
     * @param string                                                     $name    Опциональное имя маршрута
     *
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function post(string $path, array|object|string $handler, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на GET запрос
     *
     * Поучение данных. Идемпотентен. Не должен менять состояние
     *
     * @param string                                              $path    паттерн URI (например, '/users')
     * @param array{class-string, non-empty-string}|object|string $handler Метод выполняющийся для данного маршрута
     * @param string                                              $name    Опциональное имя маршрута
     *
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function get(string $path, array|object|string $handler, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на PUT запрос
     *
     * Полная замена существующего ресурса. Идемпотентен. Меняет состояние ресурса.
     *
     * @param string                                              $path    паттерн URI (например, '/users')
     * @param array{class-string, non-empty-string}|object|string $handler Метод выполняющийся для данного маршрута
     * @param string                                              $name    Опциональное имя маршрута
     *
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function put(string $path, array|object|string $handler, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на DELETE запрос
     *
     * Удаление существующего ресурса. Идемпотентен. Меняет состояние ресурса.
     *
     * @param string                                              $path    паттерн URI (например, '/users')
     * @param array{class-string, non-empty-string}|object|string $handler Метод выполняющийся для данного маршрута
     * @param string                                              $name    Опциональное имя маршрута
     *
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function delete(string $path, array|object|string $handler, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на PATH запрос
     *
     * Частичное обновление существующего ресурса. Идемпотентен или нет - зависит от логики. Меняет состояние ресурса.
     *
     * @param string                                                     $path    паттерн URI (например, '/users')
     * @param array{class-string|object, non-empty-string}|object|string $handler Метод выполняющийся для данного маршрута
     * @param string                                                     $name    Опциональное имя маршрута
     *
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function patch(string $path, array|object|string $handler, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на HEAD запрос
     *
     * Поучение данных только в заголовках. Идемпотентен. Не должен менять состояние
     *
     * @param string                                                     $path    паттерн URI (например, '/users')
     * @param array{class-string|object, non-empty-string}|object|string $handler Метод выполняющийся для данного маршрута
     * @param string                                                     $name    Опциональное имя маршрута
     *
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function head(string $path, array|object|string $handler, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на любой запрос
     *
     * @param string                                                     $path    паттерн URI (например, '/users')
     * @param array{class-string|object, non-empty-string}|object|string $handler Метод выполняющийся для данного маршрута
     * @param string                                                     $name    Опциональное имя маршрута
     *
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function any(string $path, array|object|string $handler, string $name = ''): RouteInterface;

    /**
     * Регистрация маршрута отвечающего на запрос заданного перечня методов.
     *
     * @param list<HttpMethod>                                           $methods Список допустимых методов
     * @param string                                                     $path    паттерн URI (например, '/users')
     * @param array{class-string|object, non-empty-string}|object|string $handler Метод выполняющийся для данного маршрута
     * @param string                                                     $name    Опциональное имя маршрута
     *
     * @return RouteInterface Зарегистрированный объект маршрута
     */
    public function match(
        array $methods,
        string $path,
        array|object|string $handler,
        string $name = '',
    ): RouteInterface;

    /**
     * Направляет входящий HTTP-запрос обработчику соответствующего маршрута.
     *
     * @param HttpRequest $request входящий запрос
     *
     * @return mixed Результат возвращаемый обработчиком маршрута
     *
     * @throws NotFoundException Выбрасывается в случае если подходящий маршрут не найден
     */
    public function dispatch(HttpRequest $request): mixed;

    /**
     * Поиск маршрута для входящего запроса.
     *
     * @param HttpRequest $request Входящий запрос
     *
     * @return null|RouteInterface Возвращает объект маршрута или null, если подходящий маршрут не найден
     */
    public function findRoute(HttpRequest $request): ?RouteInterface;

    /**
     * Возвращает маршрут по имени.
     *
     * @param string $name Имя маршрута
     *
     * @return null|RouteInterface Возвращает объект маршрута или null, если маршрут не найден
     */
    public function route(string $name): ?RouteInterface;

    /**
     * Назначение групп, которые автоматически добавятся, при загрузке маршрутов.
     *
     * @param list<string> $groups Список наименований групп
     *
     * @return $this
     */
    public function addAutoGroups(array $groups): static;

    /**
     * Очистка групп, которые автоматически добавятся, при загрузке маршрутов.
     *
     * @return $this
     */
    public function cleanAutoGroups(): static;
}
