<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures;

use Vasoft\Joke\Contract\Provider\ConfigurableServiceProviderInterface;
use Vasoft\Joke\Contract\Provider\ServiceProviderInterface;
use Vasoft\Joke\Tests\Fixtures\Config\SingleConfig;

class FakeExample
{
    public float $floatValue = 0;

    public function __construct(public int $num) {}

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

    public function setFloat(float $value): void
    {
        $this->floatValue = $value;
    }

    public function setConfig(SingleConfig $config): void {}

    public function props(
        int|string|float $value,
        ServiceProviderInterface&ConfigurableServiceProviderInterface $provider,
    ): void {}
}
