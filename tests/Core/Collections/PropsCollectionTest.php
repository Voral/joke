<?php

namespace Vasoft\Joke\Tests\Core\Collections;

use Vasoft\Joke\Core\Collections\PropsCollection;
use PHPUnit\Framework\TestCase;

class PropsCollectionTest extends TestCase
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

    public function testDefaultValue()
    {
        $this->assertSame(null, self::$collection->get('notExists'));
        $this->assertSame('default Value', self::$collection->get('notExists', 'default Value'));
    }
    public function testGetTypes()
    {
        $this->assertSame(self::$data['string'], self::$collection->get('string'));
        $this->assertSame(self::$data['int'], self::$collection->get('int'));
        $this->assertSame(self::$data['float'], self::$collection->get('float'));
        $this->assertSame(self::$data['bool'], self::$collection->get('bool'));
        $this->assertSame(self::$data['array'], self::$collection->get('array'));
    }

    public function testGetAll()
    {
        $this->assertEquals(self::$data, self::$collection->getAll());
    }
}
