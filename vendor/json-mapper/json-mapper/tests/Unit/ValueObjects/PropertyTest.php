<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\ValueObjects;

use JsonMapper\Enums\Visibility;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\Property;
use JsonMapper\ValueObjects\PropertyType;
use PHPUnit\Framework\TestCase;

class PropertyTest extends TestCase
{
    /**
     * @covers \JsonMapper\ValueObjects\Property
     */
    public function testGettersReturnConstructorValues(): void
    {
        $propertyType = new PropertyType('int', ArrayInformation::notAnArray());
        $property = new Property('id', Visibility::PUBLIC(), false, $propertyType);

        self::assertSame('id', $property->getName());
        self::assertSame([$propertyType], $property->getPropertyTypes());
        self::assertFalse($property->isNullable());
        self::assertTrue($property->getVisibility()->equals(Visibility::PUBLIC()));
    }

    /**
     * @covers \JsonMapper\ValueObjects\Property
     */
    public function testIsUnionReturnsTrueWhenMoreThanOneType(): void
    {
        $int = new PropertyType('int', ArrayInformation::notAnArray());
        $float = new PropertyType('float', ArrayInformation::notAnArray());
        $property = new Property('id', Visibility::PUBLIC(), false, $int, $float);

        self::assertTrue($property->isUnion());
    }

    /**
     * @covers \JsonMapper\ValueObjects\Property
     */
    public function testIsUnionReturnsFalseWhenOneType(): void
    {
        $int = new PropertyType('int', ArrayInformation::notAnArray());
        $property = new Property('id', Visibility::PUBLIC(), false, $int);

        self::assertFalse($property->isUnion());
    }

    /**
     * @covers \JsonMapper\ValueObjects\Property
     */
    public function testPropertyCanBeConvertedToBuilderAndBack(): void
    {
        $property = new Property(
            'id',
            Visibility::PUBLIC(),
            false,
            new PropertyType('int', ArrayInformation::notAnArray())
        );
        $builder = $property->asBuilder();

        self::assertEquals($property, $builder->build());
    }

    /**
     * @covers \JsonMapper\ValueObjects\Property
     */
    public function testCanBeConvertedToJson(): void
    {
        $property = new Property(
            'id',
            Visibility::PUBLIC(),
            false,
            new PropertyType('int', ArrayInformation::notAnArray())
        );

        $propertyAsJson = json_encode($property);

        self::assertIsString($propertyAsJson);
        self::assertJsonStringEqualsJsonString(
            '{"name":"id","types":[{"type":"int","isArray":false, "arrayInformation":{"isArray":false,"dimensions":0}}],"visibility":"public","isNullable":false}',
            (string) $propertyAsJson
        );
    }
}
