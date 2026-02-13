<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Collections\HeadersCollection;
use Vasoft\Joke\Collections\PropsCollection;
use Vasoft\Joke\Container\BaseContainer;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Container\ApplicationContainerInterface;
use Vasoft\Joke\Contract\Container\DiContainerInterface;
use Vasoft\Joke\Contract\Container\ResolverInterface;
use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Core as CoreLegacy;
use Vasoft\Joke\Contract\Core as ContractLegacy;
use Vasoft\Joke\Foundation\Request;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\HtmlResponse;
use Vasoft\Joke\Http\Response\JsonResponse;
use Vasoft\Joke\Http\Response\Response;
use Vasoft\Joke\Routing\Route;
use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Session\SessionCollection;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Container\BaseContainer
 */
final class BackwardCompatibilityAliasesTest extends TestCase
{
    #[DataProvider('provideDeprecatedClassAliasesCases')]
    #[IgnoreDeprecations]
    public function testDeprecatedClassAliases(string $deprecatedClass, string $actualClass, object $entity): void
    {
        $container = new ServiceContainer();
        $container->registerSingleton($actualClass, $entity);
        self::assertSame($entity, $container->get($deprecatedClass));
        self::assertSame($entity, $container->get($actualClass));
    }

    public static function provideDeprecatedClassAliasesCases(): iterable
    {
        yield 'ServiceContainer' => [
            CoreLegacy\ServiceContainer::class,
            ServiceContainer::class,
            new ServiceContainer(),
        ];
        yield 'BaseContainer' => [
            CoreLegacy\BaseContainer::class,
            BaseContainer::class,
            new ServiceContainer(),
        ];
        yield 'Route' => [
            CoreLegacy\Routing\Route::class,
            Route::class,
            new \stdClass(),
        ];
        yield 'Router' => [
            CoreLegacy\Routing\Router::class,
            Router::class,
            new \stdClass(),
        ];
        yield 'Request' => [
            CoreLegacy\Request\Request::class,
            Request::class,
            new \stdClass(),
        ];
        yield 'HttpRequest' => [
            CoreLegacy\Request\HttpRequest::class,
            HttpRequest::class,
            new \stdClass(),
        ];
        yield 'Response' => [
            CoreLegacy\Response\Response::class,
            Response::class,
            new \stdClass(),
        ];
        yield 'HtmlResponse' => [
            CoreLegacy\Response\HtmlResponse::class,
            HtmlResponse::class,
            new \stdClass(),
        ];
        yield 'JsonResponse' => [
            CoreLegacy\Response\JsonResponse::class,
            JsonResponse::class,
            new \stdClass(),
        ];
        yield 'SessionCollection' => [
            CoreLegacy\Collections\Session::class,
            SessionCollection::class,
            new \stdClass(),
        ];
        yield 'HeadersCollection' => [
            CoreLegacy\Collections\HeadersCollection::class,
            HeadersCollection::class,
            new \stdClass(),
        ];
        yield 'PropsCollection' => [
            CoreLegacy\Collections\PropsCollection::class,
            PropsCollection::class,
            new \stdClass(),
        ];
        yield 'ApplicationContainerInterface' => [
            ContractLegacy\ApplicationContainerInterface::class,
            ApplicationContainerInterface::class,
            new \stdClass(),
        ];
        yield 'DiContainerInterface' => [
            ContractLegacy\DiContainerInterface::class,
            DiContainerInterface::class,
            new \stdClass(),
        ];
        yield 'MiddlewareInterface' => [
            ContractLegacy\Middlewares\MiddlewareInterface::class,
            MiddlewareInterface::class,
            new \stdClass(),
        ];
        yield 'ResolverInterface' => [
            ContractLegacy\Routing\ResolverInterface::class,
            ResolverInterface::class,
            new \stdClass(),
        ];
    }

    #[IgnoreDeprecations]
    public function testDeprecatedParameterResolver(): void
    {
        $container = new ServiceContainer();
        self::assertSame(
            $container->getParameterResolver(),
            $container->get(CoreLegacy\Routing\ParameterResolver::class),
        );
    }
}
