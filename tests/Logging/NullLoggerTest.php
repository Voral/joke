<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Logging;

use Vasoft\Joke\Contract\Logging\LoggerInterface;
use Vasoft\Joke\Logging\LogLevel;
use Vasoft\Joke\Logging\NullLogger;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Logging\NullLogger
 */
final class NullLoggerTest extends TestCase
{
    public function testImplementsLoggerInterface(): void
    {
        $logger = new NullLogger();
        self::assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testAllMethodsExecuteWithoutError(): void
    {
        $logger = new NullLogger();
        $message = 'Test message';
        $context = ['key' => 'value'];

        $logger->emergency($message, $context);
        $logger->alert($message, $context);
        $logger->critical($message, $context);
        $logger->error($message, $context);
        $logger->warning($message, $context);
        $logger->notice($message, $context);
        $logger->info($message, $context);
        $logger->debug($message, $context);

        $logger->log(LogLevel::INFO, $message, $context);

        self::assertTrue(true);
    }

    public function testAcceptsObjectMessage(): void
    {
        $logger = new NullLogger();
        $message = new class {
            public function __toString(): string
            {
                return 'Object message';
            }
        };

        $logger->info($message);
        self::assertTrue(true);
    }
}
