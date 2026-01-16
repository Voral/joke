<?php

namespace Vasoft\Joke\Tests\Core\Collections;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Core\Collections\HeadersCollection;
use PHPUnit\Framework\TestCase;

class HeadersCollectionTest extends TestCase
{

    public static function dataProviderSetHeader(): array
    {
        return [
            ['Content-Type', 'application/json', 'contentType']
        ];
    }

    #[dataProvider('dataProviderSetHeader')]
    public function testSetContentType(string $name, string $value, string $propertyName): void
    {
        $collection = new HeadersCollection([]);
        $collection->setContentType($value);
        $all = $collection->getAll();

        self::assertEquals($value, $collection->$propertyName);
        self::assertCount(1, $all);
        self::assertEquals($value, $all[$name] ?? 'not set');
    }
}
