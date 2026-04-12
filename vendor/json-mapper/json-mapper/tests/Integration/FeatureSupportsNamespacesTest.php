<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\ComplexObject;
use PHPUnit\Framework\TestCase;
use JsonMapper\Tests\Implementation\Php74;

/**
 * @coversNothing
 */
class FeatureSupportsNamespacesTest extends TestCase
{
    /**
     * @requires PHP >= 7.4
     */
    public function testItMapsClassFromTheSameNamespace(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Php74\PopoWrapper();
        $json = (object) ['wrappee' => (object) ['name' => 'two']];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertNotNull($object->wrappee);
        self::assertSame('two', $object->wrappee->name);
    }

    public function testItCanMapAnObjectWithACustomClassAttributeFromAnotherNamespace(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new ComplexObject();
        $json = (object) ['user' => (object) ['name' => __METHOD__]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(__METHOD__, $object->getUser()->getName());
    }
}
