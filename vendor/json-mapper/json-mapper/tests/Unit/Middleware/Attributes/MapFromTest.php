<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware\Attributes;

use JsonMapper\Middleware\Attributes\MapFrom;
use PHPUnit\Framework\TestCase;

class MapFromTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\Attributes\MapFrom
     */
    public function testConstructorSetsProperties(): void
    {
        $source = __CLASS__;
        $mapFrom = new MapFrom($source);

        self::assertEquals(__CLASS__, $mapFrom->source);
    }
}
