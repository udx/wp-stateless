<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsUnionTypesTest extends TestCase
{
    /**
     * @dataProvider scalarValueDataTypes
     * @param int|float|string|bool $value
     */
    public function testItCanMapAScalarUnionType($value): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var int|float|string|bool */
            public $value;
        };
        $json = (object) ['value' => $value];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals($value, $object->value);
    }

    /**
     * @dataProvider scalarValueDataTypes
     * @param int|float|string|bool $value
     */
    public function testItCanMapAnArrayOfScalarUnionType($value): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var int[]|float[]|string[]|bool[] */
            public $values;
        };
        $json = (object) ['values' => [$value]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals([$value], $object->values);
    }

    public function testItCanMapAUnionOfUnixTimeStampAndDateTimeWithDateTimeObject(): void
    {
        // Arrange
        $now = new \DateTime();
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /**
             * Either a unix timestamp (int) or a date time object
             * @var int|\DateTime
             */
            public $moment;
        };
        $json = (object) ['moment' => $now->format('Y-m-d\TH:i:s.uP')];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals($now, $object->moment);
    }

    public function scalarValueDataTypes(): array
    {
        return [
            'string' => ['Some string'],
            'boolean' => [true],
            'integer' => [1],
            'float' => [M_PI],
        ];
    }
}
