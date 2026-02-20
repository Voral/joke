<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Config;

use Vasoft\Joke\Config\AbstractConfig;

class SecondSingleConfig extends AbstractConfig
{
    private int $value = 0;

    public function __construct(public readonly string $context = 'default') {}

    public function setValue(int $value): void
    {
        $this->guard();
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
