<?php

declare(strict_types=1);

namespace Vasoft\Joke\Application;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Middleware\CsrfMiddleware;
use Vasoft\Joke\Middleware\ExceptionMiddleware;
use Vasoft\Joke\Middleware\MiddlewareCollection;
use Vasoft\Joke\Middleware\SessionMiddleware;
use Vasoft\Joke\Middleware\StdMiddleware;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\Routing\StdGroup;

class CoreServiceProvider extends AbstractProvider
{
    public function __construct(
        private readonly ServiceContainer $serviceContainer,
    ) {}

    public function register(): void
    {
        $this->serviceContainer->registerSingleton('middleware.global', MiddlewareCollection::class);
        $this->serviceContainer->registerSingleton('middleware.route', MiddlewareCollection::class);
    }

    public function boot(): void
    {
        /** @var MiddlewareCollection $middlewares */
        $middlewares = $this->serviceContainer->get('middleware.global');
        $middlewares->addMiddleware(ExceptionMiddleware::class, StdMiddleware::EXCEPTION->value);
        /** @var MiddlewareCollection $middlewares */
        $routeMiddlewares = $this->serviceContainer->get('middleware.route');
        $routeMiddlewares
            ->addMiddleware(SessionMiddleware::class, StdMiddleware::SESSION->value)
            ->addMiddleware(CsrfMiddleware::class, StdMiddleware::CSRF->value, [StdGroup::WEB->value]);
    }

    public function provides(): array
    {
        return [
            'middleware.global',
            'middleware.route',
        ];
    }
}
