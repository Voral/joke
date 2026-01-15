<?php

namespace Vasoft\Joke\Tests\Core\Exceptions;

use Vasoft\Joke\Core\Exceptions\JokeException;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Response\ResponseStatus;

class JokeExceptionTest extends TestCase
{
    public function testDefaultResponseStatus()
    {
        $exception = new class extends JokeException {
        };
        $this->assertEquals(ResponseStatus::INTERNAL_SERVER_ERROR, $exception->getResponseStatus());
    }
}
