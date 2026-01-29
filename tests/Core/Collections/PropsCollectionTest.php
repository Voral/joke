<?php

namespace Vasoft\Joke\Tests\Core\Collections;

use Vasoft\Joke\Core\Collections\PropsCollection;
use PHPUnit\Framework\TestCase;

class PropsCollectionTest extends TestCase
{
    public function testReset(): void
    {
        $data = [
            'string' => 'string',
            'int' => 1,
        ];
        $collection = new PropsCollection($data);
        self::assertEquals($data, $collection->getAll());
        $newData = ['string' => 'someTest', 'int' => 2];
        $collection->reset($newData);
        self::assertEquals($newData, $collection->getAll());
    }

    public function testSetProperty(): void
    {
        $collection = new PropsCollection([]);
        self::assertNull($collection->get('example'));
        $collection->set('example', 456);
        self::assertSame(456, $collection->get('example'));
    }

    public function testUnset(): void
    {
        $collection = new PropsCollection(['example' => 1234]);
        self::assertSame(1234, $collection->get('example'));
        $collection->unset('example');
        self::assertNull($collection->get('example'));
    }
}
