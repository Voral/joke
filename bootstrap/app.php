<?php

require __DIR__ . '/../vendor/autoload.php';

use Vasoft\Joke\Core\Application;
use Vasoft\Joke\Core\ServiceContainer;
return new Application(dirname(__DIR__), '/routes/web.php', new ServiceContainer());
