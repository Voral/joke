<?php

namespace Vasoft\Joke\Tests\Core\Routing\Exceptions;

use Vasoft\Joke\Core\Response\ResponseStatus;
use Vasoft\Joke\Core\Routing\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;

class NotFoundExceptionTest extends TestCase
{

    public function testGetResponseStatus()
    {
        $exception = new NotFoundException();
        $this->assertEquals(ResponseStatus::NOT_FOUND, $exception->getResponseStatus());
    }
}
