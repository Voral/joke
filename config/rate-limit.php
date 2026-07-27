<?php

declare(strict_types=1);

use Vasoft\Joke\RateLimit\RateLimitConfig;

return (new RateLimitConfig())
    ->setEnabled(true)
    ->setStoragePath(sys_get_temp_dir() . '/joke_ratelimit')
    ->setWithHeaders(true)
    ->setProfile('default', 100, 3600)      // 100 запросов за час
    ->setProfile('auth', 5, 60)             // 5 попыток входа в минуту
    ->setProfile('api', 1000, 3600)         // 1000 запросов за час для API
;
