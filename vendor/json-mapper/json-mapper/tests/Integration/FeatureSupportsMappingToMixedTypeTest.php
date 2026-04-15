<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\ComplexObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsMappingToMixedTypeTest extends TestCase
{
    /**
     * @dataProvider scalarValueDataTypes
     * @param mixed $value
     */
    public function testItSetsTheValueAsIsForMixedType($value): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new ComplexObject();
        $json = (object) ['mixedParam' => $value];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame($value, $object->mixedParam);
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
