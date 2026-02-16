<?php

declare(strict_types=1);

namespace Vasoft\Joke\Logging;

use Vasoft\Joke\Contract\Logging\LoggerInterface;

class NullLogger implements LoggerInterface
{
    public function emergency(object|string $message, array $context = []): void {}

    public function alert(object|string $message, array $context = []): void {}

    public function critical(object|string $message, array $context = []): void {}

    public function error(object|string $message, array $context = []): void {}

    public function warning(object|string $message, array $context = []): void {}

    public function notice(object|string $message, array $context = []): void {}

    public function info(object|string $message, array $context = []): void {}

    public function debug(object|string $message, array $context = []): void {}

    public function log(LogLevel $level, object|string $message, array $context = []): void {}
}
