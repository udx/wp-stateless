<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\Tests\Implementation\SimpleObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsMappingFromJsonStringTest extends TestCase
{
    public function testItCanMapAnObjectFromString(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json =  '{"name": "one"}';

        // Act
        $mapper->mapObjectFromString($json, $object);

        // Assert
        self::assertSame('one', $object->name);
    }

    public function testItCanMapArrayFromString(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new SimpleObject();
        $json = '[{"name": "one"}, {"name": "two"}]';

        // Act
        $result = $mapper->mapArrayFromString($json, $object);

        // Assert
        self::assertContainsOnly(SimpleObject::class, $result);
        self::assertSame('one', $result[0]->getName());
        self::assertSame('two', $result[1]->getName());
    }

    public function testItWillThrowAnExceptionWhenMappingArrayFromStringWithJsonObject(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = '{"name": "one"}';
        $this->expectException(\RuntimeException::class);

        // Act
        $mapper->mapArrayFromString($json, $object);
    }

    public function testItWillThrowAnExceptionWhenMappingObjectFromStringWithJsonArray(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = '[{"name": "one"}, {"name": "two"}]';
        $this->expectException(\RuntimeException::class);

        // Act
        $mapper->mapObjectFromString($json, $object);
    }

    public function testItWillThrowExceptionOnInvalidJson(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $jsonString =  '{"name": one}';
        $this->expectException(\JsonException::class);

        // Act
        $mapper->mapObjectFromString($jsonString, $object);
    }
}
