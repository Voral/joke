<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Exceptions;

use Vasoft\Joke\Exceptions\JokeException;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\Response\ResponseStatus;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Exceptions\JokeException
 */
final class JokeExceptionTest extends TestCase
{
    public function testDefaultResponseStatus(): void
    {
        $exception = new class extends JokeException {};
        self::assertSame(ResponseStatus::INTERNAL_SERVER_ERROR, $exception->getResponseStatus());
    }
}
