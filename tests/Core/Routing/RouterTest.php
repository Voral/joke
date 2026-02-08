<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core\Routing;

use Vasoft\Joke\Core\Request\HttpMethod;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Routing\Exceptions\NotFoundException;
use Vasoft\Joke\Core\Routing\Router;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\ServiceContainer;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\Routing\Router
 */
final class RouterTest extends TestCase
{
    protected static ServiceContainer $serviceContainer;

    public static function setUpBeforeClass(): void
    {
        self::$serviceContainer = new ServiceContainer();
        parent::setUpBeforeClass();
    }

    public function testRouterTypes(): void
    {
        $router = new Router(self::$serviceContainer);
        $routeGet = $router->get('/get', static fn() => 'get', 'route-get');
        $routePost = $router->post('/post', static fn() => 'post', 'route-post');
        $routePut = $router->put('/put', static fn() => 'put', 'route-put');
        $routeDelete = $router->delete('/delete', static fn() => 'delete', 'route-delete');
        $routePatch = $router->patch('/patch', static fn() => 'patch', 'route-patch');
        $routeHead = $router->head('/head', static fn() => 'head', 'route-head');

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/get']);
        self::assertSame(spl_object_id($routeGet), spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::GET, $router->findRoute($request)->method);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/post']);
        self::assertSame(spl_object_id($routePost), spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::POST, $router->findRoute($request)->method);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/put']);
        self::assertSame(spl_object_id($routePut), spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::PUT, $router->findRoute($request)->method);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/delete']);
        self::assertSame(spl_object_id($routeDelete), spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::DELETE, $router->findRoute($request)->method);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'PATCH', 'REQUEST_URI' => '/patch']);
        self::assertSame(spl_object_id($routePatch), spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::PATCH, $router->findRoute($request)->method);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'HEAD', 'REQUEST_URI' => '/head']);
        self::assertSame(spl_object_id($routeHead), spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::HEAD, $router->findRoute($request)->method);
    }

    public function testRouterAny(): void
    {
        $router = new Router(self::$serviceContainer);
        $route = $router->any('/get', static fn() => 'get', 'route-get');
        $routeId = spl_object_id($route);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/get']);
        self::assertSame($routeId, spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::GET, $router->findRoute($request)->method);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/get']);
        self::assertNotSame($routeId, spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::POST, $router->findRoute($request)->method);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/get']);
        self::assertNotSame($routeId, spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::PUT, $router->findRoute($request)->method);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/get']);
        self::assertNotSame($routeId, spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::DELETE, $router->findRoute($request)->method);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'PATCH', 'REQUEST_URI' => '/get']);
        self::assertNotSame($routeId, spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::PATCH, $router->findRoute($request)->method);

        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'HEAD', 'REQUEST_URI' => '/get']);
        self::assertNotSame($routeId, spl_object_id($router->findRoute($request)));
        self::assertSame(HttpMethod::HEAD, $router->findRoute($request)->method);
    }

    public function testRouterNotImplementedMethod(): void
    {
        $router = new Router(self::$serviceContainer);
        $router->get('/get', static fn() => 'get', 'route-get');
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/get']);
        self::assertNull($router->findRoute($request));
    }

    public function testRouterNotImplementedRout(): void
    {
        $router = new Router(self::$serviceContainer);
        $router->get('/get', static fn() => 'get', 'route-get');
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/unknown']);
        self::assertNull($router->findRoute($request));
    }

    public function testByName(): void
    {
        $router = new Router(self::$serviceContainer);
        $named = $router->get('/get', static fn() => 'get', 'route-get');
        $autoNamed = $router->match([HttpMethod::GET, HttpMethod::POST], '/get', static fn() => 'get');

        self::assertSame($named, $router->route('route-get'));
        self::assertSame($autoNamed, $router->route('get#post|/get'));
    }

    public function testDispatchSuccess(): void
    {
        $router = new Router(self::$serviceContainer);
        $router->get('/get', static fn() => 'get response', 'route-get');
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/get']);

        self::assertSame('get response', $router->dispatch($request));
    }

    public function testDispatchFail(): void
    {
        $router = new Router(self::$serviceContainer);
        $router->get('/get', static fn() => 'get response', 'route-get');
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/unknown']);
        self::expectException(NotFoundException::class);
        self::expectExceptionMessage('Route not found');

        $router->dispatch($request);
    }
}
