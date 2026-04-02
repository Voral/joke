<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response\Html;

use Vasoft\Joke\Http\Response\Html\AttributeCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\Html\AttributeCollection
 */
final class AttributeCollectionTest extends TestCase
{
    public function testSetAttributes(): void
    {
        $collection = new AttributeCollection([
            'alt' => 'test',
            'disabled' => true,
        ]);
        $collection->set('attr1', 'value0');
        $collection->set('attr1', 'value1');
        $collection->set('attr2', 'value2');

        self::assertSame('alt="test" disabled attr1="value1" attr2="value2"', $collection->getAttributes());
    }

    public function testAppendAttributes(): void
    {
        $collection = new AttributeCollection();
        $collection->set('attr1', 'value1');
        $collection->append('attr1', 'value1_1');
        $collection->append('attr2', 'value2');
        $collection->flag('attr3', true);
        $collection->append('attr3', 'value3');
        $collection->set('style', 'v4_1');
        $collection->append('style', 'v4_2');

        self::assertSame(
            'attr1="value1 value1_1" attr2="value2" attr3="value3" style="v4_1;v4_2"',
            $collection->getAttributes(),
        );
    }

    public function testUnsetAttributes(): void
    {
        $collection = new AttributeCollection();
        $collection->set('attr1', 'value1');
        $collection->set('attr2', 'value2');
        $collection->remove('attr1');

        self::assertSame('attr2="value2"', $collection->getAttributes());
    }

    public function testNormalize(): void
    {
        $collection = new AttributeCollection();
        $collection->set('attr1', '"><script>alert(1)</script><"');
        $collection->set('attr2', 'example "first" ');

        self::assertSame(
            'attr1="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;&lt;&quot;" attr2="example &quot;first&quot; "',
            $collection->getAttributes(),
        );
    }

    public function testFlag(): void
    {
        $collection = new AttributeCollection();
        $collection->set('attr1', 'value1');
        $collection->flag('attr1', true);
        $collection->flag('attr2', true);
        $collection->flag('attr2', false);

        self::assertSame('attr1', $collection->getAttributes());
    }

    public function testSeparator(): void
    {
        $collection = new AttributeCollection(
            ['style' => 's1', 'custom' => 'c1', 'extends' => 'e1'],
            ['style' => '; ', 'custom' => '#'],
        );
        $collection->append('style', 's2');
        $collection->append('custom', 'c2');
        $collection->append('extends', 'e2');
        $collection->setAttributeSeparator('extends', '=');


        self::assertSame('style="s1; s2" custom="c1#c2" extends="e1=e2"', $collection->getAttributes());
    }
}
