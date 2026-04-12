<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\Popo;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsMappingToPublicPropertiesTest extends TestCase
{
    public function testItCanMapAnObjectUsingAPublicProperty(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = (object) ['name' => __METHOD__];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(__METHOD__, $object->name);
    }

    public function testItAppliesTypeCastingWhenMappingAnObjectUsingAPublicProperty(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = (object) ['name' => 42];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame('42', $object->name);
    }
}
