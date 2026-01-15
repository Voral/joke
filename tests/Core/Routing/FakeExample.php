<?php

namespace Vasoft\Joke\Tests\Core\Routing;

class FakeExample
{
    public function __construct(public int $value) { }

    public static function tryFrom(int $value): ?self
    {
        return new self($value);
    }

    public static function exampleClosure(int $num, int $page): int
    {
        return $num + $page;
    }

    public static function exampleClosureStatic(int $num, int $page): int
    {
        return $num + $page;
    }
}

if (!function_exists('exampleClosureFunction')) {
    function exampleClosureFunction(int $num, int $page): int
    {
        return $num + $page;
    }
}