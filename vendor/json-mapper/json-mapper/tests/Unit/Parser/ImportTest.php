<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Parser;

use JsonMapper\Parser\Import;
use PHPUnit\Framework\TestCase;

class ImportTest extends TestCase
{
    /**
     * @covers \JsonMapper\Parser\Import
     */
    public function testCanHoldProperties(): void
    {
        $import = new Import(\stdClass::class, null);

        self::assertEquals(\stdClass::class, $import->getImport());
        self::assertNull($import->getAlias());
        self::assertFalse($import->hasAlias());
    }

    /**
     * @covers \JsonMapper\Parser\Import
     */
    public function testCanHoldPropertiesWithAlias(): void
    {
        $import = new Import(\stdClass::class, 'someAlias');

        self::assertEquals(\stdClass::class, $import->getImport());
        self::assertEquals('someAlias', $import->getAlias());
        self::assertTrue($import->hasAlias());
    }
}
