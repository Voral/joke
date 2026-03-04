<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Logger;

use Vasoft\Joke\Logging\AbstractLogger;
use Vasoft\Joke\Logging\LogLevel;

class FakeLogger extends AbstractLogger
{
    public string $errorThrowException = '';
    private array $records = [];

    public function log(LogLevel $level, object|string $message, array $context = []): void
    {
        $this->records[] = compact('level', 'message', 'context');
    }

    public function error(object|string $message, array $context = []): void
    {
        if ('' !== $this->errorThrowException) {
            throw new \Exception($this->errorThrowException);
        }
        parent::error($message, $context);
    }

    public function getRecords(): array
    {
        return $this->records;
    }
}
