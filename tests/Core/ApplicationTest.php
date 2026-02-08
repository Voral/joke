<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Vasoft\Joke\Contract\Core\Routing\RouterInterface;
use Vasoft\Joke\Core\Application;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Routing\Router;
use Vasoft\Joke\Core\ServiceContainer;
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Tests\Fixtures\Core\Middlewares\SingleMiddleware;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\Application
 */
final class ApplicationTest extends TestCase
{
    public function testLoadingEnvironment(): void
    {
        $di = new ServiceContainer();
        new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            $di,
        );
        $byAlias = $di->get('env');
        $byClass = $di->get(Environment::class);
        self::assertInstanceOf(Environment::class, $byAlias);
        self::assertSame($byAlias, $byClass);
    }

    public function testExecuteDefaultHtml(): void
    {
        $di = new ServiceContainer();
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            $di,
        );
        ob_start();
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $app->handle($request);
        $output = ob_get_clean();
        self::assertStringContainsString('<li><a href="/name/Alex">Hi Alex</a>', $output);
        self::assertSame($request, $di->get(HttpRequest::class));
    }

    public function testExecuteDefaultJson(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer(),
        );
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/json/Alex']));
        $output = ob_get_clean();
        self::assertSame('{"fio":"Alex"}', $output);
    }

    public function testDefaultMiddleware(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            '/tests/Fixtures/routes/web-no-wildcard.php',
            new ServiceContainer(),
        );
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/not-found-url']));
        $output = ob_get_clean();
        self::assertSame('{"message":"Route not found"}', $output);
    }

    public function testWildCard(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer(),
        );
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/not-found-url']));
        $output = ob_get_clean();
        self::assertSame('Запрошен несуществующий путь: not-found-url', $output);
    }

    #[RunInSeparateProcess]
    public function testDefaultRouteMiddleware(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer(),
        );
        self::assertSame(PHP_SESSION_NONE, session_status());
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/Alex']));
        $output = ob_get_clean();
        self::assertSame(PHP_SESSION_ACTIVE, session_status(), 'Session middleware is not active');
        self::assertSame('Hi Alex', $output);
    }

    public function testAddMiddleware(): void
    {
        $middleware = new SingleMiddleware();
        $middleware->index = 3;
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer(),
        )
            ->addMiddleware(SingleMiddleware::class)
            ->addMiddleware($middleware);
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/jons']));
        $output = ob_get_clean();
        self::assertSame('Middleware 0 begin#Middleware 3 begin#Hi jons#Middleware 3 end#Middleware 0 end', $output);
    }

    public function testAddMiddlewareAndRouteMiddleware(): void
    {
        $middleware = new SingleMiddleware();
        $middleware->index = 3;
        $routeMiddleware = new SingleMiddleware();
        $routeMiddleware->index = 4;
        $routeMiddleware2 = new SingleMiddleware();
        $routeMiddleware2->index = 5;

        $diContainer = new ServiceContainer();
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            $diContainer,
        )
            ->addMiddleware(SingleMiddleware::class)
            ->addMiddleware($middleware)
            ->addRouteMiddleware($routeMiddleware);
        /** @var Router $router */
        $router = $diContainer->get(RouterInterface::class);
        $route = $router->route('hiName');
        $route->addMiddleware($routeMiddleware2);

        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/jons']));
        $output = ob_get_clean();
        self::assertSame(
            'Middleware 0 begin#Middleware 3 begin#Middleware 4 begin#Middleware 5 begin#Hi jons#Middleware 5 end#Middleware 4 end#Middleware 3 end#Middleware 0 end',
            $output,
        );
    }

    public function testAddMiddlewareAndRouteMiddlewareFilter(): void
    {
        $middleware = new SingleMiddleware();
        $middleware->index = 3;
        $routeMiddleware1 = new SingleMiddleware();
        $routeMiddleware1->index = 4;
        $routeMiddleware2 = new SingleMiddleware();
        $routeMiddleware2->index = 5;
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer(),
        )
            ->addMiddleware(SingleMiddleware::class)
            ->addMiddleware($middleware)
            ->addRouteMiddleware($routeMiddleware1)
            ->addRouteMiddleware($routeMiddleware2, groups: ['filtered']);

        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/jons']));
        $output = ob_get_clean();
        self::assertSame(
            'Middleware 0 begin#Middleware 3 begin#Middleware 4 begin#Hi jons#Middleware 4 end#Middleware 3 end#Middleware 0 end',
            $output,
        );
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name-filtered/jons']));
        $output = ob_get_clean();
        self::assertSame(
            'Middleware 0 begin#Middleware 3 begin#Middleware 4 begin#Middleware 5 begin#Hi jons#Middleware 5 end#Middleware 4 end#Middleware 3 end#Middleware 0 end',
            $output,
        );
    }

    public function testWrongMiddleware(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer(),
        )->addMiddleware(Router::class);
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/jons']));
        $output = ob_get_clean();
        self::assertSame(
            '{"message":"\'Middleware Vasoft\\\Joke\\\Core\\\Routing\\\Router must implements MiddlewareInterface"}',
            $output,
        );
    }
}
