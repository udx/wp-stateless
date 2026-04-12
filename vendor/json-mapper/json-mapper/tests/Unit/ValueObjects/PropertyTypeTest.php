<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\ValueObjects;

use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\PropertyType;
use PHPUnit\Framework\TestCase;

class PropertyTypeTest extends TestCase
{
    /**
     * @covers \JsonMapper\ValueObjects\PropertyType
     */
    public function testGettersReturnConstructorValues(): void
    {
        $arrayInformation = ArrayInformation::notAnArray();
        $propertyType = new PropertyType('int', $arrayInformation);

        self::assertSame('int', $propertyType->getType());
        self::assertEquals($arrayInformation, $propertyType->getArrayInformation());
    }

    /**
     * @covers \JsonMapper\ValueObjects\PropertyType
     */
    public function testCanBeConvertedToJson(): void
    {
        $propertyType = new PropertyType('int', ArrayInformation::notAnArray());

        $propertyAsJson = json_encode($propertyType);

        self::assertIsString($propertyAsJson);
        self::assertJsonStringEqualsJsonString(
            '{"type":"int","isArray":false,"arrayInformation":{"isArray":false,"dimensions":0}}',
            (string) $propertyAsJson
        );
    }

    /**
     * @covers \JsonMapper\ValueObjects\PropertyType
     * @dataProvider isArrayValueAndExpectation
     */
    public function testIsArrayReturnsCorrectForPossibleValues(ArrayInformation $isArray, bool $expected): void
    {
        $propertyType = new PropertyType('int', $isArray);

        self::assertSame($expected, $propertyType->isArray());
    }

    /**
     * @covers \JsonMapper\ValueObjects\PropertyType
     * @dataProvider isMultiDimensionalArrayValueAndExpectation
     */
    public function testIsMultiDimensionalArrayReturnsCorrectForPossibleValues(
        ArrayInformation $isArray,
        bool $expected
    ): void {
        $propertyType = new PropertyType('int', $isArray);

        self::assertSame($expected, $propertyType->isMultiDimensionalArray());
    }

    public function isArrayValueAndExpectation(): array
    {
        return [
            'no' => [ArrayInformation::notAnArray(), false],
            'single dimensional' => [ArrayInformation::singleDimension(), true],
            'multi dimensional' => [ArrayInformation::multiDimension(2), true,]
        ];
    }

    public function isMultiDimensionalArrayValueAndExpectation(): array
    {
        return [
            'no' => [ArrayInformation::notAnArray(), false],
            'single dimensional' => [ArrayInformation::singleDimension(), false],
            'multi dimensional' => [ArrayInformation::multiDimension(2), true,]
        ];
    }
}
