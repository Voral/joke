<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response\Html\Asset;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\Response\Html\Asset\Asset;
use Vasoft\Joke\Http\Response\Html\Asset\AssetPosition;
use Vasoft\Joke\Http\Response\Html\AttributeCollection;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\Html\Asset\Asset
 */
final class AssetTest extends TestCase
{
    public function testDefaultAttributeCollection(): void
    {
        $asset = new Asset('https://example.com/asset.js', null, 100, AssetPosition::HEAD);
        self::assertInstanceOf(AttributeCollection::class, $asset->attributes);
    }

    public function testNotDefaultAttributeCollection(): void
    {
        $attributes = new AttributeCollection();
        $asset = new Asset('https://example.com/asset.js', $attributes, 100, AssetPosition::HEAD);
        self::assertSame($attributes, $asset->attributes);
    }
}
