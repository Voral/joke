<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Logging;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Logging\DefaultMessageFormatter;

/**
 * @internal
 *
 * @coversDefaultClass  \Vasoft\Joke\Logging\DefaultMessageFormatter
 */
final class DefaultMessageFormatterTest extends TestCase
{
    private DefaultMessageFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new DefaultMessageFormatter(3);
    }

    public function testInterpolatesPlaceholders(): void
    {
        $message = 'User {name} has {count} messages';
        $context = ['name' => 'Alex', 'count' => 42];

        $result = $this->formatter->interpolate($message, $context);

        self::assertSame('User Alex has 42 messages', $result);
    }

    public function testHandlesStringMessage(): void
    {
        $message = 'Plain message';
        $result = $this->formatter->interpolate($message);

        self::assertSame('Plain message', $result);
    }

    public function testConvertsObjectWithToString(): void
    {
        $message = new class {
            public function __toString(): string
            {
                return 'Custom object message';
            }
        };

        $result = $this->formatter->interpolate($message);

        self::assertSame('Custom object message', $result);
    }

    public function testConvertsThrowable(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new \RuntimeException('Main error', 0, $previous);

        $result = $this->formatter->interpolate($exception);

        self::assertStringContainsString('Main error', $result);
        self::assertStringContainsString('Previous error', $result);
        self::assertStringContainsString('Stack trace:', $result);
    }

    public function testConvertsObjectInContext(): void
    {
        $obj = new class {
            public function __toString(): string
            {
                return 'Context object';
            }
        };

        $message = 'Value: {value}';
        $context = ['value' => $obj];

        $result = $this->formatter->interpolate($message, $context);

        self::assertSame('Value: Context object', $result);
    }

    public function testSerializesArrayInContext(): void
    {
        $obj = new class {
            public function __toString(): string
            {
                return 'Context object';
            }
        };
        $message = 'Data: {data}';
        $context = ['data' => ['a' => 1, 'b' => [2, 3], 'object' => $obj]];

        $result = $this->formatter->interpolate($message, $context);
        self::assertStringContainsString('a => 1', $result);
        self::assertStringContainsString('b => [0 => 2, 1 => 3]', $result);
        self::assertStringContainsString('object => Context object', $result);
    }

    public function testLimitsArrayDepth(): void
    {
        $deepArray = ['level1' => ['level2' => ['level3' => ['level4' => 'deep']]]];
        $message = 'Deep: {arr}';
        $context = ['arr' => $deepArray];

        $shallowFormatter = new DefaultMessageFormatter(2);
        $result = $shallowFormatter->interpolate($message, $context);

        self::assertStringContainsString('level2 => [...]', $result);
        self::assertStringNotContainsString('level3', $result);
    }

    public function testIgnoresUnknownPlaceholders(): void
    {
        $message = 'Hello {name}, your balance is {balance}';
        $context = ['name' => 'Alex'];

        $result = $this->formatter->interpolate($message, $context);

        self::assertSame('Hello Alex, your balance is {balance}', $result);
    }

    public function testHandlesEmptyContext(): void
    {
        $message = 'No placeholders here';
        $result = $this->formatter->interpolate($message, []);

        self::assertSame('No placeholders here', $result);
    }

    public function testConvertsNonStringableObjectToEmptyString(): void
    {
        $message = 'Object: {obj}';
        $context = ['obj' => new \stdClass()];

        $result = $this->formatter->interpolate($message, $context);

        self::assertSame('Object: ', $result);
    }
}
