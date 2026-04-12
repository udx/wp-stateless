<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\Rename\Rename;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\Models\User;
use JsonMapper\Tests\Implementation\SimpleObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportRenamingOfJsonPropertiesTest extends TestCase
{
    public function testItCanRenameJsonProperties(): void
    {
        // Arrange
        $rename = new Rename();
        $rename->addMapping(User::class, 'Full-Name', 'name');
        $rename->addMapping(User::class, 'Identifier', 'id');
        $mapper = (new JsonMapperFactory())->bestFit();
        $mapper->unshift($rename);
        $object = new User();
        $json = (object) ['Full-Name' => 'John Doe', 'Identifier' => '42'];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals('John Doe', $object->getName());
        self::assertEquals(42, $object->getId());
    }

    public function testItCanRenameJsonPropertiesOnNestedObjects(): void
    {
        // Arrange
        $rename = new Rename();
        $rename->addMapping(SimpleObject::class, 'FULL-NAME', 'name');
        $rename->addMapping(ComplexObject::class, 'sub', 'children');
        $mapper = (new JsonMapperFactory())->bestFit();
        $mapper->unshift($rename);
        $object = new ComplexObject();
        $json = (object) ['sub' => [(object) ['FULL-NAME' => 'John Doe'], (object) ['FULL-NAME' => 'Jane Doe']]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertCount(2, $object->getChildren());
        self::assertEquals([new SimpleObject('John Doe'), new SimpleObject('Jane Doe')], $object->getChildren());
    }
}
