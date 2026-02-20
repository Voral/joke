<?php

/** @var Environment $env */
declare(strict_types=1);

use Vasoft\Joke\Application\ApplicationConfig;
use Vasoft\Joke\Config\Environment;

return new ApplicationConfig()
    ->setFileRoues('routes/web.php');


return static fn(): ApplicationConfig => new ApplicationConfig()->setFileRoues('routes/web.php');
