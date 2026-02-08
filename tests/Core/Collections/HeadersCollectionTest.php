<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core\Collections;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Core\Collections\HeadersCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\Collections\HeadersCollection
 */
final class HeadersCollectionTest extends TestCase
{
    #[DataProvider('provideSetContentTypeCases')]
    public function testSetContentType(string $name, string $value, string $propertyName): void
    {
        $collection = new HeadersCollection([]);
        $collection->setContentType($value);
        $all = $collection->getAll();

        self::assertSame($value, $collection->{$propertyName});
        self::assertCount(1, $all);
        self::assertSame($value, $all[$name] ?? 'not set');
    }

    public static function provideSetContentTypeCases(): iterable
    {
        return [
            ['Content-Type', 'application/json', 'contentType'],
        ];
    }
}
