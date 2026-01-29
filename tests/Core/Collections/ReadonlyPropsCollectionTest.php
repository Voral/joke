<?php

namespace Core\Collections;

use Vasoft\Joke\Core\Collections\PropsCollection;
use PHPUnit\Framework\TestCase;

class ReadonlyPropsCollectionTest extends TestCase
{
    private static array $data = [
        'string' => 'string',
        'int' => 1,
        'float' => 1.1,
        'bool' => true,
        'array' => [1, 2, 3],
    ];
    private static ?PropsCollection $collection = null;

    public static function setUpBeforeClass(): void
    {
        self::$collection = new PropsCollection(self::$data);
        parent::setUpBeforeClass();
    }

    public function testDefaultValue(): void
    {
        self::assertSame(null, self::$collection->get('notExists'));
        self::assertSame('default Value', self::$collection->get('notExists', 'default Value'));
    }

    public function testGetTypes(): void
    {
        self::assertSame(self::$data['string'], self::$collection->get('string'));
        self::assertSame(self::$data['int'], self::$collection->get('int'));
        self::assertSame(self::$data['float'], self::$collection->get('float'));
        self::assertSame(self::$data['bool'], self::$collection->get('bool'));
        self::assertSame(self::$data['array'], self::$collection->get('array'));
    }

    public function testGetAll(): void
    {
        self::assertEquals(self::$data, self::$collection->getAll());
    }
}
