<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperBuilder;
use JsonMapper\Tests\Implementation\SimpleObjectExtension;
use JsonMapper\Tests\Implementation\Php74\SimpleObjectExtension as TypedSimpleObjectExtension;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsMappingToParentPrivateProperties extends TestCase
{
    public function testItCanMapAnObjectUsingPrivatePropertiesInTheParentClassUsingDocBlocks(): void
    {
        // Arrange
        $mapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->build();
        $json = (object) ['name' => __METHOD__];

        // Act
        $object = $mapper->mapToClass($json, SimpleObjectExtension::class);

        // Assert
        self::assertSame(__METHOD__, $object->getName());
    }

    public function testItCanMapAnObjectUsingPrivatePropertiesInTheParentClassUsingTypedProperties(): void
    {
        // Arrange
        $mapper = JsonMapperBuilder::new()
            ->withTypedPropertiesMiddleware()
            ->build();
        $json = (object) ['name' => __METHOD__];

        // Act
        $object = $mapper->mapToClass($json, TypedSimpleObjectExtension::class);

        // Assert
        self::assertSame(__METHOD__, $object->getName());
    }
}
