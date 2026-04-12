<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\SimpleObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsMappingToPublicSettersTest extends TestCase
{
    public function testItCanMapAnObjectUsingAPublicSetter(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new SimpleObject();
        $json = (object) ['name' => __METHOD__];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(__METHOD__, $object->getName());
    }

    public function testItAppliesTypeCastingWhenMappingAnObjectUsingAPublicSetter(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new SimpleObject();
        $json = (object) ['name' => 42];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame('42', $object->getName());
    }
}
