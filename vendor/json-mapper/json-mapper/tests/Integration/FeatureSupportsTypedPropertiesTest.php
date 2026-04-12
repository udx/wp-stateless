<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use PHPUnit\Framework\TestCase;
use JsonMapper\Tests\Implementation\Php74;

/**
 * @coversNothing
 */
class FeatureSupportsTypedPropertiesTest extends TestCase
{
    /**
     * @requires PHP >= 7.4
     */
    public function testItCanMapAnObjectWithTypedProperties(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Php74\Popo();
        $json = (object) ['name' => __METHOD__];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(__METHOD__, $object->name);
    }

    /**
     * @requires PHP >= 7.4
     */
    public function testItAppliesTypeCastingMappingAnObjectWithTypedProperties(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Php74\Popo();
        $json = (object) ['name' => 42];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame('42', $object->name);
    }

    /**
     * @requires PHP >= 7.4
     */
    public function testItHandlesPropertyTypedAsArray(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Php74\Popo();
        $json = (object) ['friends' => [__METHOD__, __CLASS__]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame([__METHOD__, __CLASS__], $object->friends);
    }
}
