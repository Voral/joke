<?php

namespace Core\Collections;

use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Core\Collections\PropsCollection;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Exceptions\JokeException;

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

    public function testHas():void
    {
        self::assertTrue(self::$collection->has('string'));
        self::assertFalse(self::$collection->has('integer'));
    }

    public function testGetOrFailDefault(): void
    {
        self::expectException(JokeException::class);
        self::expectExceptionMessage('Property "unknown" does not exist.');
        self::$collection->getOrFail('unknown');
    }
    public function testGetOrFailSuccess(): void
    {
        self::assertSame(self::$data['string'], self::$collection->getOrFail('string'));
        self::assertSame(self::$data['int'], self::$collection->getOrFail('int'));
        self::assertSame(self::$data['float'], self::$collection->getOrFail('float'));
        self::assertSame(self::$data['bool'], self::$collection->getOrFail('bool'));
        self::assertSame(self::$data['array'], self::$collection->getOrFail('array'));
    }
    public function testGetOrFailCustom(): void
    {
        self::expectException(JokeException::class);
        self::expectExceptionMessage('unknown does not exist.');
        self::$collection->getOrFail('unknown',fn(string $key) => new ConfigException($key . ' does not exist.'));
    }
}
