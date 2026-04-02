<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response\Html;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\Response\Html\StringCollection;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\Html\StringCollection
 */
final class StringCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $expected = <<<'HTML'
            <script>alert(1)</script>
            <script>alert(2)</script>
            HTML;

        $collection = new StringCollection("\n");
        $collection->add('<script>alert(1)</script>');
        $collection->add('<script>alert(2)</script>');
        self::assertSame($expected, $collection->build());
    }

    public function testCollectionNoDoubles(): void
    {
        $expected = '<script>alert(1)</script><script>alert(2)</script>';

        $collection = new StringCollection('');
        $collection->add('<script>alert(1)</script>');
        $collection->add('<script>alert(2)</script>');
        $collection->add('<script>alert(1)</script>');
        self::assertSame($expected, $collection->build());
    }
}
