<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsMappingToStdClass extends TestCase
{
    public function testItCanMapCustomClassWithStdClassProperty(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $response = new class {
            /** @var \stdClass */
            public $properties;
        };
        $json = (object) ['properties' => (object) ['one' => 1, 'two' => 2]];

        // Act
        $mapper->mapObject($json, $response);

        // Assert
        self::assertEquals((object) ['one' => 1, 'two' => 2], $response->properties);
    }

    public function testItCanMapCustomClassWithStdClassPropertyFromArray(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $response = new class {
            /** @var \stdClass */
            public $properties;
        };
        $json = (object) ['properties' => ['one' => 1, 'two' => 2]];

        // Act
        $mapper->mapObject($json, $response);

        // Assert
        self::assertEquals((object) ['one' => 1, 'two' => 2], $response->properties);
    }
}
