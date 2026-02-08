<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core\Exceptions;

use Vasoft\Joke\Core\Exceptions\JokeException;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Response\ResponseStatus;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\Exceptions\JokeException
 */
final class JokeExceptionTest extends TestCase
{
    public function testDefaultResponseStatus(): void
    {
        $exception = new class extends JokeException {};
        self::assertSame(ResponseStatus::INTERNAL_SERVER_ERROR, $exception->getResponseStatus());
    }
}
