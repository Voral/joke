<?php

declare(strict_types=1);

namespace Vasoft\Joke\Application;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\UnknownConfigException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Provider\ConfigurableServiceProviderInterface;
use Vasoft\Joke\Middleware\CsrfMiddleware;
use Vasoft\Joke\Middleware\ExceptionMiddleware;
use Vasoft\Joke\Middleware\MiddlewareCollection;
use Vasoft\Joke\Middleware\SessionMiddleware;
use Vasoft\Joke\Middleware\StdMiddleware;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\Routing\StdGroup;

class KernelServiceProvider extends AbstractProvider implements ConfigurableServiceProviderInterface
{
    /** @deprecated Только для обратной совместимости - будет удален в версии 2.0 */
    public static string $legacyPathRouteFile =  'routes/web.php';
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

    public static function provideConfigs(): array
    {
        return [ApplicationConfig::class];
    }

    public static function buildConfig(string $configClass, ServiceContainer $container): AbstractConfig
    {
        if (ApplicationConfig::class === $configClass) {
            return new ApplicationConfig()->setFileRoues(self::$legacyPathRouteFile);
        }

        throw new UnknownConfigException($configClass);
    }
}
