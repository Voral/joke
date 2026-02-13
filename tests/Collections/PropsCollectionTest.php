<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Collections;

use Vasoft\Joke\Collections\PropsCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Collections\PropsCollection
 */
final class PropsCollectionTest extends TestCase
{
    public function testReset(): void
    {
        $data = [
            'string' => 'string',
            'int' => 1,
        ];
        $collection = new PropsCollection($data);
        self::assertSame($data, $collection->getAll());
        $newData = ['string' => 'someTest', 'int' => 2];
        $collection->reset($newData);
        self::assertSame($newData, $collection->getAll());
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
