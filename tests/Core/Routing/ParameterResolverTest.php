<?php

namespace Vasoft\Joke\Tests\Core\Routing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Collections\PropsCollection;
use Vasoft\Joke\Core\Routing\Exceptions\AutowiredException;
use Vasoft\Joke\Core\Routing\ParameterResolver;

include_once __DIR__ . '/FakeExample.php';
class ParameterResolverTest extends TestCase
{
    public static function dataProviderClosure(): array
    {
        $testObject = new FakeExample(0);
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

    /**
     * @param $closure
     * @return void
     */
    #[DataProvider('dataProviderClosure')]
    public function testClosure($closure): void
    {
        $props = new PropsCollection(['page' => 1, 'num' => 2]);
        $resolver = new ParameterResolver();
        self::assertEquals([2, 1], $resolver->resolve($props, $closure));
    }

    public function testResolveObject(): void
    {
        $callback = function (int $page, FakeExample $num) {
            return $page + $num->value;
        };
        $props = new PropsCollection(['page' => 1, 'num' => 2]);
        $resolver = new ParameterResolver();
        $args = $resolver->resolve($props, $callback);
        self::assertInstanceOf(FakeExample::class, $args[1]);
    }

    public function testResolveObjectException(): void
    {
        $callback = function (int $a, \stdClass $b) {
            return $a + $b->value;
        };
        $props = new PropsCollection(['page' => 1, 'num' => 2]);
        $resolver = new ParameterResolver();
        self::expectException(AutowiredException::class);
        self::expectExceptionMessage('Failed to autowire parameter "$b": expected type "stdClass" cannot be resolved or is incompatible with the provided value.');
        $resolver->resolve($props, $callback);
    }
}
