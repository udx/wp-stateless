<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\ArrayOfSimpleObjects;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\ListOfSimpleObjects;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\Tests\Implementation\SimpleObject;
use PHPUnit\Framework\TestCase;
use JsonMapper\Tests\Implementation\Php74;

/**
 * @coversNothing
 */
class FeatureSupportsMappingToArrayTypesTest extends TestCase
{
    /**
     * @requires PHP >= 7.4
     */
    public function testItCanMapArrayOfObjectWithTypeHintAndDocBlock(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $response = new Php74\Response();
        $json = (object) ['data' => [(object) ['name' => 'John Doe'], (object) ['name' => 'Jane Doe']]];

        // Act
        $mapper->mapObject($json, $response);

        // Assert
        self::assertCount(2, $response->data);
        self::assertContainsOnlyInstancesOf(Php74\Popo::class, $response->data);
        $john = new Php74\Popo();
        $john->name = 'John Doe';
        $jane = new Php74\Popo();
        $jane->name = 'Jane Doe';
        self::assertEquals([$john, $jane], $response->data);
    }

    public function testItCanMapAnObjectWithAnArrayOfScalarValues(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new ComplexObject();
        $one = new SimpleObject();
        $one->setName('ONE');
        $two = new SimpleObject();
        $two->setName('TWO');
        $json = (object) ['children' => [(object) ['name' => 'ONE'], (object) ['name' => 'TWO']]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertIsArray($object->getChildren());
        self::assertContainsOnly(SimpleObject::class, $object->getChildren());
        self::assertEquals([$one, $two], $object->getChildren());
    }

    public function testItHandlesPropertyDocumentedAsArrayProvidedAsObject(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = (object) ['notes' => (object) ['one' => __METHOD__, 'two' => __CLASS__]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(['one' => __METHOD__, 'two' => __CLASS__], $object->notes);
    }

    public function testItHandlesPropertyDocumentedAsListOfObjects(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $json = json_encode((object) ['objects' => [(object) ['name' => 'ONE'], (object) ['name' => 'TWO']]]);

        // Act
        $object = $mapper->mapToClassFromString($json, ListOfSimpleObjects::class);

        // Assert
        $result = new ListOfSimpleObjects();
        $result->objects = [
            new SimpleObject('ONE'),
            new SimpleObject('TWO'),
        ];
        self::assertEquals($result, $object);
    }

    public function testItHandlesPropertyDocumentedAsArrayGtLtOfObjects(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $json = json_encode((object) ['objects' => [(object) ['name' => 'ONE'], (object) ['name' => 'TWO']]]);

        // Act
        $object = $mapper->mapToClassFromString($json, ArrayOfSimpleObjects::class);

        // Assert
        $result = new ArrayOfSimpleObjects();
        $result->objects = [
            new SimpleObject('ONE'),
            new SimpleObject('TWO'),
        ];
        self::assertEquals($result, $object);
    }
}
