<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Cache;

use JsonMapper\Cache\NullCache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class NullCacheTest extends TestCase
{
    /**
     * @covers \JsonMapper\Cache\NullCache
     */
    public function testCanBeConstructedWithoutExceptons(): void
    {
        $sut = new NullCache();

        self:self::assertInstanceOf(CacheInterface::class, $sut);
    }
}
