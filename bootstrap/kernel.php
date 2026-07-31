<?php

declare(strict_types=1);

use Vasoft\Joke\Application\KernelConfig;
use Vasoft\Joke\Provider\RateLimiterServiceProvider;

return (new KernelConfig())
    ->addProvider(RateLimiterServiceProvider::class);
