<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Config\Config;
use Vasoft\Joke\Config\ConfigLoader;
use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;
use Vasoft\Joke\Core\Exceptions\ParameterResolveException;
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
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Config\EnvironmentLoader;

/**
 * Основной класс приложения Joke.
 *
 * Является центральным оркестратором фреймворка, управляющим загрузкой маршрутов,
 * выполнением middleware и обработкой HTTP-запросов от начала до конца.
 * Интегрирует DI-контейнер, маршрутизатор и систему middleware в единый workflow.
 */
class Application
{
    /**
     * Базовый путь приложения.
     */
    public readonly string $basePath;
    /**
     * Коллекция глобальных middleware.
     *
     * Выполняются до определения маршрута. Используются для обработки ошибок,
     * CORS, логирования и других кросс-функциональных задач.
     */
    protected MiddlewareCollection $middlewares {
        get => $this->middlewares;
    }
    /**
     * Коллекция middleware маршрутизатора.
     *
     * Выполняются после определения маршрута, но до его обработчика.
     * Могут быть привязаны к группам маршрутов.
     */
    protected MiddlewareCollection $routeMiddlewares {
        get => $this->routeMiddlewares;
    }

    /**
     * Конструктор приложения.
     *
     * Автоматически регистрирует стандартные middleware:
     * - ExceptionMiddleware (глобальный уровень)
     * - SessionMiddleware и CsrfMiddleware (уровень маршрутизатора, группа 'web')
     *
     * @param string           $basePath         Базовый путь приложения (обычно корень проекта)
     * @param string           $routeConfigWeb   Путь к файлу web-маршрутов относительно базового пути
     * @param ServiceContainer $serviceContainer DI-контейнер
     *
     * @throws ParameterResolveException
     *
     * @todo Нормализовать пути
     */
    public function __construct(
        string $basePath,
        public readonly string $routeConfigWeb,
        public readonly ServiceContainer $serviceContainer,
    ) {
        $this->basePath = rtrim($basePath, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
        $environment = new Environment(new EnvironmentLoader($this->basePath));
        $serviceContainer->registerSingleton('env', $environment);
        $serviceContainer->registerSingleton(Environment::class, $environment);

        $configLoader = new ConfigLoader($this->basePath . 'config' . \DIRECTORY_SEPARATOR, $environment);

        $config = new Config($configLoader);
        $serviceContainer->registerSingleton('config', $config);
        $serviceContainer->registerSingleton(Config::class, $config);

        $serviceContainer->registerSingleton('env', $environment);
        $serviceContainer->registerSingleton(Environment::class, $environment);
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
     * и был найден.
     *
     * @param MiddlewareInterface|string $middleware Экземпляр или класс middleware
     * @param string                     $name       Наименование middleware для тех, которые могут быть только в единственном экземпляре
     *
     * @return $this
     */
    public function addMiddleware(MiddlewareInterface|string $middleware, string $name = ''): static
    {
        $this->middlewares->addMiddleware($middleware, $name);

        return $this;
    }

    /**
     * Добавляет middleware маршрутизатора.
     *
     * Может быть привязан к определённым группам маршрутов.
     * Именованные middleware с существующим именем будут заменены, сохраняя свою позицию в цепочке выполнения.
     *
     * @param MiddlewareInterface|string $middleware Класс или экземпляр middleware
     * @param string                     $name       Имя middleware (для возможности переопределения)
     * @param array<string>              $groups     Список групп маршрутов, к которым применяется middleware
     */
    public function addRouteMiddleware(
        MiddlewareInterface|string $middleware,
        string $name = '',
        array $groups = [],
    ): static {
        $this->routeMiddlewares->addMiddleware($middleware, $name, $groups);

        return $this;
    }

    /**
     * Преобразует относительный путь в абсолютный.
     *
     * @param string $relativePath Относительный путь
     *
     * @return string Абсолютный путь или false, если путь не существует
     */
    private function getFullPath(string $relativePath): string
    {
        return realpath(
            rtrim($this->basePath, \DIRECTORY_SEPARATOR)
            . \DIRECTORY_SEPARATOR
            . ltrim($relativePath, \DIRECTORY_SEPARATOR),
        );
    }

    /**
     * Обрабатывает входящий HTTP-запрос.
     *
     * Выполняет следующие шаги:
     * 1. Запускает глобальные middleware
     * 2. Определяет маршрут
     * 3. Запускает middleware маршрутизатора и маршрута
     * 4. Выполняет обработчик маршрута
     * 5. Отправляет ответ клиенту
     *
     * @param Request $request Входящий HTTP-запрос
     *
     * @throws ParameterResolveException
     * @throws WrongMiddlewareException  Если middleware не реализует MiddlewareInterface
     */
    public function handle(Request $request): void
    {
        $next = fn() => $this->handleRoute($request);
        $response = $this->processMiddlewares($request, $this->middlewares->getArrayForRun(), $next);
        $this->sendResponse($response);
    }

    /**
     * Отправляет ответ клиенту.
     *
     * Автоматически оборачивает простые типы в соответствующие Response-объекты:
     * - массивы → JsonResponse
     * - всё остальное → HtmlResponse
     *
     * @param mixed $response Результат обработчика маршрута
     */
    private function sendResponse(mixed $response): void
    {
        if (!$response instanceof Response) {
            if (is_array($response)) {
                $response = new JsonResponse()->setBody($response);
            } else {
                $response = new HtmlResponse()->setBody($response);
            }
        }
        $response->send();
    }

    /**
     * Обрабатывает запрос после определения маршрута.
     *
     * Регистрирует текущий запрос в DI-контейнере, находит подходящий маршрут,
     * собирает цепочку middleware и выполняет обработчик.
     *
     * @param HttpRequest $request Входящий HTTP-запрос
     *
     * @return mixed Результат выполнения обработчика маршрута
     *
     * @throws ParameterResolveException
     * @throws WrongMiddlewareException
     * @throws NotFoundException
     */
    private function handleRoute(HttpRequest $request): mixed
    {
        $this->serviceContainer->registerSingleton(HttpRequest::class, $request);
        $route = $this->serviceContainer->getRouter()?->findRoute($request);
        if (null === $route) {
            throw new NotFoundException('Route not found');
        }
        $next = static fn() => $route->run($request);
        $middlewareCollection = $this->routeMiddlewares->withMiddlewares($route->getMiddlewares());

        $middlewares = $middlewareCollection->getArrayForRun($route->getGroups());

        return $this->processMiddlewares($request, $middlewares, $next);
    }

    /**
     * Выполняет цепочку middleware.
     *
     * Строит вложенную структуру вызовов, где каждый middleware оборачивает
     * результат следующего звена цепочки.
     *
     * @param HttpRequest                       $request     Входящий HTTP-запрос
     * @param array<MiddlewareInterface|string> $middlewares Список middleware для выполнения
     * @param callable                          $next        Функция следующего звена цепочки
     *
     * @return mixed Результат выполнения цепочки
     *
     * @throws ParameterResolveException
     * @throws WrongMiddlewareException  Если middleware не реализует MiddlewareInterface
     */
    private function processMiddlewares(
        HttpRequest $request,
        array $middlewares,
        callable $next,
    ): mixed {
        foreach ($middlewares as $middleware) {
            $next = function () use ($middleware, $next, $request) {
                $instance = ($middleware instanceof MiddlewareInterface)
                    ? $middleware
                    : $this->resolveMiddleware($middleware);
                if (null === $instance) {
                    throw new WrongMiddlewareException($middleware);
                }

                return $instance->handle(
                    $request,
                    $next,
                );
            };
        }

        return $next();
    }

    /**
     * Создаёт экземпляр middleware через DI-контейнер.
     *
     * @param string $middleware Имя класса middleware
     *
     * @return null|MiddlewareInterface Экземпляр middleware или null, если класс не реализует интерфейс
     *
     * @throws ParameterResolveException
     */
    private function resolveMiddleware(string $middleware): ?MiddlewareInterface
    {
        $resolver = $this->serviceContainer->getParameterResolver();
        $args = $resolver->resolveForConstructor($middleware);
        $instance = new $middleware(...$args);

        return $instance instanceof MiddlewareInterface ? $instance : null;
    }

    /**
     * Загружает маршруты из конфигурационного файла.
     *
     * Автоматически назначает всем загружаемым маршрутам группу 'web'.
     *
     * @throws ParameterResolveException
     */
    private function loadRoutes(): void
    {
        $router = $this->serviceContainer->getRouter();
        $router->addAutoGroups([StdGroup::WEB->value]);
        $file = $this->getFullPath($this->routeConfigWeb);
        if (file_exists($file)) {
            require $file;
        }
        $router->cleanAutoGroups();
    }
}
