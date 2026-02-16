<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Logger;

use Vasoft\Joke\Logging\AbstractLogger;
use Vasoft\Joke\Logging\LogLevel;

class FakeLogger extends AbstractLogger
{
    private array $records = [];

    public function log(LogLevel $level, object|string $message, array $context = []): void
    {
        $this->records[] = compact('level', 'message', 'context');
    }

    public function getRecords(): array
    {
        return $this->records;
    }
}
