<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware\Rename;

use JsonMapper\Middleware\Rename\Mapping;
use JsonMapper\Tests\Implementation\Models\User;
use PHPUnit\Framework\TestCase;

class MappingTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\Rename\Mapping
     */
    public function testMappingCanHoldProperties(): void
    {
        $mapping = new Mapping(User::class, 'municipality', 'city');

        self::assertEquals(User::class, $mapping->getClass());
        self::assertEquals('municipality', $mapping->getFrom());
        self::assertEquals('city', $mapping->getTo());
    }
}
