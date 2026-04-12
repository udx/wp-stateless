<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\ComplexObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsMappingToNullableTypesTest extends TestCase
{
    public function testItCanMapANullableArrayOfScalarValues(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var int[]|null */
            public $numbers;
        };
        $json = (object) ['numbers' => null];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertNull($object->numbers);
    }

    public function testItCanMapANullableArrayOfObjects(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var \DateTime[]|null */
            public $dates;
        };
        $json = (object) ['dates' => null];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertNull($object->dates);
    }

    public function testItCanMapAnObjectWithANullClassAttribute(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new ComplexObject();
        $json = (object) ['child' => null];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertNull($object->getChild());
    }
}
