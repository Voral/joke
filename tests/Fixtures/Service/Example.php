<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Service;

class Example
{
    public function __construct(public readonly SingleService $parent) {}
}
