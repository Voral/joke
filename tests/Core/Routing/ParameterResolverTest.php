<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core\Routing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Exceptions\ParameterResolveException;
use Vasoft\Joke\Core\Routing\Exceptions\AutowiredException;
use Vasoft\Joke\Core\Routing\ParameterResolver;
use Vasoft\Joke\Core\ServiceContainer;
use Vasoft\Joke\Tests\Fixtures\Service\SingleService;

include_once __DIR__ . '/FakeExample.php';

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\Routing\ParameterResolver
 */
final class ParameterResolverTest extends TestCase
{
    protected static ServiceContainer $serviceContainer;

    public static function setUpBeforeClass(): void
    {
        self::$serviceContainer = new ServiceContainer();
        parent::setUpBeforeClass();
    }

    #[DataProvider('provideClosureCases')]
    public function testClosure($closure): void
    {
        $resolver = new ParameterResolver(self::$serviceContainer);
        self::assertSame([2, 1], $resolver->resolveForCallable($closure, ['page' => 1, 'num' => 2]));
    }

    public static function provideClosureCases(): iterable
    {
        $testObject = new FakeExample(0);

        return [
            [
                static fn($num, $page) => $num + $page,
            ],
            [FakeExample::exampleClosureStatic(...)],
            [$testObject->exampleClosure(...)],
            ['\Vasoft\Joke\Tests\Core\Routing\FakeExample::exampleClosureStatic'],
            ['\Vasoft\Joke\Tests\Core\Routing\exampleClosureFunction'],
            [[FakeExample::class, 'exampleClosureStatic']],
            [[$testObject, 'exampleClosure']],
        ];
    }

    public function testResolveObject(): void
    {
        $callback = static fn(int $page, FakeExample $num) => $page + $num->num;
        $resolver = new ParameterResolver(self::$serviceContainer);
        $args = $resolver->resolveForCallable($callback, ['page' => 1, 'num' => 2]);
        self::assertInstanceOf(FakeExample::class, $args[1]);
    }

    public function testResolveObjectException(): void
    {
        $callback = static fn(int $a, \stdClass $b) => $a + $b->value;
        $resolver = new ParameterResolver(self::$serviceContainer);
        self::expectException(AutowiredException::class);
        self::expectExceptionMessage(
            'Failed to autowire parameter "$b": expected type "stdClass" cannot be resolved or is incompatible with the provided value.',
        );
        $resolver->resolveForCallable($callback, ['b' => 1, 'a' => 2]);
    }

    public function testAutowiredService(): void
    {
        $callback = static fn(int $a, SingleService $b) => $a + $b->getValue();
        self::$serviceContainer->registerSingleton(SingleService::class, SingleService::class);
        $resolver = new ParameterResolver(self::$serviceContainer);
        $args = $resolver->resolveForCallable($callback, ['a' => 12]);
        self::assertCount(2, $args);
    }

    public function testAutowiredUnknown(): void
    {
        $callback = static fn(int $a, \SingleServiceUnknown $b) => $a + $b->getValue();
        $resolver = new ParameterResolver(self::$serviceContainer);
        self::expectException(AutowiredException::class);
        self::expectExceptionMessage(
            'Failed to autowire parameter "$b": expected type "SingleServiceUnknown" cannot be resolved or is incompatible with the provided value.',
        );
        $args = $resolver->resolveForCallable($callback, ['a' => 12]);
    }

    public function testAutowiredServiceNotRegistered(): void
    {
        $callback = static fn(int $a, FakeExample $b) => $a + $b->value;
        $resolver = new ParameterResolver(self::$serviceContainer);
        self::expectException(AutowiredException::class);
        self::expectExceptionMessage(
            'Failed to autowire parameter "$b": expected type "Vasoft\Joke\Tests\Core\Routing\FakeExample" cannot be resolved or is incompatible with the provided value.',
        );
        $resolver->resolveForCallable($callback, ['a' => 12]);
    }

    public function testAutowiredScalar(): void
    {
        $callback = static fn(int $a, $b) => $a + $b->getValue();
        $resolver = new ParameterResolver(self::$serviceContainer);
        self::expectException(AutowiredException::class);
        self::expectExceptionMessage(
            'Failed to autowire parameter "$b": expected type "scalar" cannot be resolved or is incompatible with the provided value.',
        );
        $resolver->resolveForCallable($callback, ['a' => 12]);
    }

    public function testResolveForConstructor(): void
    {
        $resolver = new ParameterResolver(self::$serviceContainer);
        $args = $resolver->resolveForConstructor(FakeExample::class, ['num' => 12, 'z' => 14]);
        self::assertCount(1, $args);
        self::assertSame(12, $args[0]);
    }

    public function testResolveForCallableThrowsOnInvalidCallable(): void
    {
        $container = self::createStub(ServiceContainer::class);
        $resolver = new ParameterResolver($container);

        $this->expectException(ParameterResolveException::class);
        $resolver->resolveForCallable('NonExistentClass::nonExistentMethod');
    }

    public function testResolveForConstructorThrowsOnNonExistentClass(): void
    {
        $container = self::createStub(ServiceContainer::class);
        $resolver = new ParameterResolver($container);

        $this->expectException(ParameterResolveException::class);
        $resolver->resolveForConstructor('Totally\NonExistent\ClassName');
    }
}
