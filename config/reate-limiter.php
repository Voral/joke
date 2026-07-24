<?php

declare(strict_types=1);

use Vasoft\Joke\RateLimiter\RateLimiterConfig;

return new RateLimiterConfig()
    ->setRules([
        'default' => ['limit' => 3, 'window' => 60],
    ]);
