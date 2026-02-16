<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Logging;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Logging\AbstractLogger;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Logging\LogLevel;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Logging\AbstractLogger
 */
final class AbstractLoggerTest extends TestCase
{
    #[DataProvider('provideLogCases')]
    public function testLog(LogLevel $expectedLevel, string $methodName): void
    {
        $expectedMessage = 'Test ' . random_int(0, 9999);
        $expectedContext = ['example' => random_int(0, 9999)];
        $valueLevel = null;
        $valueMessage = '';
        $valueContext = [];
        $logger = $this->getMockBuilder(AbstractLogger::class)
            ->onlyMethods(['log'])
            ->getMock();
        $logger->expects(self::once())->method('log')->willReturnCallback(
            static function (LogLevel $level, object|string $message, array $context = []) use (
                &$valueLevel,
                &$valueMessage,
                &$valueContext
            ): void {
                $valueLevel = $level;
                $valueMessage = $message;
                $valueContext = $context;
            },
        );
        $logger->{$methodName}($expectedMessage, $expectedContext);
        self::assertSame($expectedLevel, $valueLevel);
        self::assertSame($expectedMessage, $valueMessage);
        self::assertSame($expectedContext, $valueContext);
    }

    public static function provideLogCases(): iterable
    {
        yield [LogLevel::DEBUG, 'debug'];
        yield [LogLevel::INFO, 'info'];
        yield [LogLevel::NOTICE, 'notice'];
        yield [LogLevel::WARNING, 'warning'];
        yield [LogLevel::ERROR, 'error'];
        yield [LogLevel::CRITICAL, 'critical'];
        yield [LogLevel::ALERT, 'alert'];
        yield [LogLevel::EMERGENCY, 'emergency'];
    }
}
