<?php

namespace Vasoft\Joke\Tests\Core\Routing;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Core\Request\HttpMethod;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Routing\Route;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/FakeExample.php';

class RouteTest extends TestCase
{

    public static function dataProviderRun(): array
    {
        $testObject = new  FakeExample(0);

        return [
            [
                function ($num, $page) {
                    return $num + $page;
                }
            ],
            [FakeExample::exampleClosureStatic(...),],
            [$testObject->exampleClosure(...)],
            ['\Vasoft\Joke\Tests\Core\Routing\FakeExample::exampleClosureStatic'],
            ['\Vasoft\Joke\Tests\Core\Routing\exampleClosureFunction'],
            [[FakeExample::class, 'exampleClosureStatic']],
            [[$testObject, 'exampleClosure']],
        ];
    }

    public function testCompilePattern(): void
    {
        $route = new Route('/api/{section}/{num:id}/{page:int}/{filter:slug}', HttpMethod::GET, function () { });
        self::assertSame(
            '#^/api/(?P<section>[^/]+)/(?P<num>\d+)/(?P<page>\d+)/(?P<filter>[a-z0-9\-_]+)$#i',
            $route->compiledPattern
        );
    }

    public function testWithMethod(): void
    {
        $route = new Route('/', HttpMethod::GET, function () { });
        self::assertEquals(HttpMethod::GET, $route->method);
        $route2 = $route->withMethod(HttpMethod::POST);
        self::assertEquals(HttpMethod::POST, $route2->method);
        self::assertNotEquals($route, $route2);
    }

    public function testMatchesSuccess(): void
    {
        $route = new Route('/api/{section}/{num:id}/{page:int}/{filter:slug}', HttpMethod::GET, function () { });
        $request = new HttpRequest(server: [
            'REQUEST_URI' => '/api/orders/1/2/closed'
        ]);
        self::assertTrue($route->matches($request));
        self::assertEquals([
            'section' => 'orders',
            'num' => 1,
            'page' => 2,
            'filter' => 'closed',
        ], $request->props->getAll());
    }

    public function testMatchesFail(): void
    {
        $route = new Route('/api/{section}/{num:id}/{page:int}/{filter:slug}', HttpMethod::GET, function () { });
        $request = new HttpRequest(server: [
            'REQUEST_URI' => '/rest/orders/1/2/closed'
        ]);
        self::assertFalse($route->matches($request));
    }

    #[DataProvider('dataProviderRun')]
    public function testRun($closure): void
    {
        $route = new Route('/api/{num:id}/{page:int}', HttpMethod::GET, $closure);
        $request = new HttpRequest(server: ['REQUEST_URI' => '/api/1/2']);
        $route->matches($request);
        self::assertEquals(3, $route->run($request));
    }
}
