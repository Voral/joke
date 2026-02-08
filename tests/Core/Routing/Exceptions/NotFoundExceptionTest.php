<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core\Routing\Exceptions;

use Vasoft\Joke\Core\Response\ResponseStatus;
use Vasoft\Joke\Core\Routing\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\Routing\Exceptions\NotFoundException
 */
final class NotFoundExceptionTest extends TestCase
{
    public function testGetResponseStatus(): void
    {
        $exception = new NotFoundException();
        self::assertSame(ResponseStatus::NOT_FOUND, $exception->getResponseStatus());
    }
}
