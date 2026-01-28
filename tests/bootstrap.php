<?php

ini_set('opcache.enable', 0);
ini_set('opcache.enable_cli', 0);

if (($_ENV['JK_ENV'] ?? $_SERVER['JK_ENV'] ?? getenv('JK_ENV')) !== 'testing') {
    fwrite(STDERR, "Ошибка: тесты должны запускаться в окружении 'testing'!\n");
    exit(1);
}