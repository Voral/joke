<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Config;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Environment;

class SingleConfig extends AbstractConfig
{
    private int $value = 0;

    public function __construct(private readonly ?Environment $environment = null) {}

    public function setValue(int $value): void
    {
        $this->guard();
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getEnvValue(string $name, string $default): string
    {
        return $this->environment->get($name, $default);
    }
}
