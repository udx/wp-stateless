<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\ValueObjects;

use JsonMapper\ValueObjects\ArrayInformation;
use PHPUnit\Framework\TestCase;

class ArrayInformationTest extends TestCase
{
    /**
     * @covers \JsonMapper\ValueObjects\ArrayInformation
     */
    public function testNotAnArrayContainsCorrectValues(): void
    {
        $arrayInformation = ArrayInformation::notAnArray();

        self::assertFalse($arrayInformation->isArray());
        self::assertEquals(0, $arrayInformation->getDimensions());
    }

    /**
     * @covers \JsonMapper\ValueObjects\ArrayInformation
     */
    public function testSingleDimensionContainsCorrectValues(): void
    {
        $arrayInformation = ArrayInformation::singleDimension();

        self::assertTrue($arrayInformation->isArray());
        self::assertEquals(1, $arrayInformation->getDimensions());
    }

    /**
     * @covers \JsonMapper\ValueObjects\ArrayInformation
     */
    public function testMultiDimensionContainsCorrectValues(): void
    {
        $dimensions = 3;
        $arrayInformation = ArrayInformation::multiDimension($dimensions);

        self::assertTrue($arrayInformation->isArray());
        self::assertEquals($dimensions, $arrayInformation->getDimensions());
    }

    /**
     * @covers \JsonMapper\ValueObjects\ArrayInformation
     * @dataProvider isMultiDimensionalArrayProvider
     */
    public function testIsMultiDimensionalArrayReturnsCorrectValue(
        ArrayInformation $arrayInformation,
        bool $isMultidimensional
    ): void {
        self::assertEquals($isMultidimensional, $arrayInformation->isMultiDimensionalArray());
    }

    /**
     * @covers \JsonMapper\ValueObjects\ArrayInformation
     */
    public function testCanBeConvertedToJson(): void
    {
        $arrayInformation = ArrayInformation::multiDimension(2);

        $arrayInformationAsJson = json_encode($arrayInformation);

        self::assertIsString($arrayInformationAsJson);
        self::assertJsonStringEqualsJsonString(
            '{"isArray":true,"dimensions":2}',
            (string) $arrayInformationAsJson
        );
    }

    /**
     * @covers \JsonMapper\ValueObjects\ArrayInformation
     * @dataProvider equalsDataProvider
     */
    public function testEquals(ArrayInformation $left, ArrayInformation $right, bool $isEqual): void
    {
        self::assertEquals($isEqual, $left->equals($right));
    }

    public function isMultiDimensionalArrayProvider(): array
    {
        return [
            'not an array' => [ArrayInformation::notAnArray(), false],
            'single dimension array' => [ArrayInformation::singleDimension(), false],
            'multi dimension array' => [ArrayInformation::multiDimension(2), true],
        ];
    }

    public function equalsDataProvider(): array
    {
        return [
            'left and right equals' => [ArrayInformation::notAnArray(), ArrayInformation::notAnArray(), true],
            'dimensions differ' => [ArrayInformation::multiDimension(2), ArrayInformation::multiDimension(3), false],
            'array vs. not an array differ'
                => [ArrayInformation::singleDimension(), ArrayInformation::notAnArray(), false],
        ];
    }
}
