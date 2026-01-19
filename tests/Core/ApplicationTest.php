<?php

namespace Vasoft\Joke\Tests\Core;

use Vasoft\Joke\Core\Application;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Routing\Router;
use Vasoft\Joke\Core\ServiceContainer;
use Vasoft\Joke\Tests\Fixtures\Core\Middlewares\SingleMiddleware;

class ApplicationTest extends TestCase
{
    public function testExecuteDefaultHtml(): void
    {
        $di = new ServiceContainer();
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            $di
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
            new ServiceContainer()
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
            '/routes/web.php',
            new ServiceContainer()
        );
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/not-found-url']));
        $output = ob_get_clean();
        self::assertSame('{"message":"Route not found"}', $output);
    }

    public function testAddMiddleware(): void
    {
        $middleware = new SingleMiddleware();
        $middleware->index = 3;
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer()
        )
            ->addMiddleware(SingleMiddleware::class)
            ->addMiddleware($middleware);
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/jons']));
        $output = ob_get_clean();
        self::assertSame('Middleware 0 begin#Middleware 3 begin#Hi jons#Middleware 3 end#Middleware 0 end', $output);
    }

    public function testWrongMiddleware(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer()
        )->addMiddleware(Router::class);
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/jons']));
        $output = ob_get_clean();
        self::assertSame(
            '{"message":"\'Middleware Vasoft\\\\Joke\\\\Core\\\\Routing\\\\Router must implements MiddlewareInterface"}',
            $output
        );
    }
}
