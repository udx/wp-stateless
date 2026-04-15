<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Cache;

use JsonMapper\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class ArrayCacheTest extends TestCase
{
    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testCanBeConstructedWithoutExceptons(): void
    {
        $sut = new ArrayCache();

        self:self::assertInstanceOf(CacheInterface::class, $sut);
    }
}
