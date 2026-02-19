<?php

declare(strict_types=1);

namespace Vasoft\Joke\Application;

use Vasoft\Joke\Config\Config;
use Vasoft\Joke\Config\ConfigLoader;
use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Middleware\CsrfMiddleware;
use Vasoft\Joke\Middleware\ExceptionMiddleware;
use Vasoft\Joke\Middleware\Exceptions\WrongMiddlewareException;
use Vasoft\Joke\Middleware\MiddlewareCollection;
use Vasoft\Joke\Middleware\SessionMiddleware;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\HtmlResponse;
use Vasoft\Joke\Http\Response\JsonResponse;
use Vasoft\Joke\Http\Response\Response;
use Vasoft\Joke\Provider\ProviderManagerBuilder;
use Vasoft\Joke\Routing\Exceptions\NotFoundException;
use Vasoft\Joke\Routing\StdGroup;
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Config\EnvironmentLoader;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Support\Normalizers\Path;

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
        $pathNormalizer = new Path($basePath);
        $this->basePath = $pathNormalizer->basePath;
        $serviceContainer->registerSingleton(Path::class, $pathNormalizer);
        $serviceContainer->registerAlias('pathNormalizer', Path::class);

        $environment = new Environment(new EnvironmentLoader($pathNormalizer->basePath));
        $serviceContainer->registerSingleton(Environment::class, $environment);
        $serviceContainer->registerAlias('env', Environment::class);

        $configLoader = new ConfigLoader('config', $environment, $pathNormalizer);
        $config = new Config($configLoader);
        $serviceContainer->registerSingleton(Config::class, $config);
        $serviceContainer->registerAlias('config', Config::class);

        $providers = array_merge(
            [CoreServiceProvider::class],
            $config->get('app.providers', []),
        );
        $providerManager = ProviderManagerBuilder::build(
            $this->serviceContainer,
            $providers,
            $config->get('app.deferredProviders', []),
        );
        $providerManager->register();
        $providerManager->boot();
        $this->middlewares = $this->serviceContainer->get('middleware.global');
        $this->routeMiddlewares = $this->serviceContainer->get('middleware.route');

        $this->loadRoutes($pathNormalizer);
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
     * Обрабатывает входящий HTTP-запрос.
     *
     * Выполняет следующие шаги:
     * 1. Запускает глобальные middleware
     * 2. Определяет маршрут
     * 3. Запускает middleware маршрутизатора и маршрута
     * 4. Выполняет обработчик маршрута
     * 5. Отправляет ответ клиенту
     *
     * @param HttpRequest $request Входящий HTTP-запрос
     *
     * @throws ParameterResolveException
     * @throws WrongMiddlewareException  Если middleware не реализует MiddlewareInterface
     */
    public function handle(HttpRequest $request): void
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
        $route = $this->serviceContainer->getRouter()->findRoute($request);
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
     *
     * @deprecated
     */
    private function loadRoutes(Path $pathNormalizer): void
    {
        $router = $this->serviceContainer->getRouter();
        $router->addAutoGroups([StdGroup::WEB->value]);
        $file = $pathNormalizer->normalizeFile($this->routeConfigWeb);
        if (file_exists($file)) {
            require $file;
        }
        $router->cleanAutoGroups();
    }
}
