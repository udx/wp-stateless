<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\Popo;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsDateTimeTypesTest extends TestCase
{
    public function testItCanMapAnDateTimeImmutableProperty(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = (object) ['date' => '2020-03-08 12:42:14'];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals(new \DateTimeImmutable('2020-03-08 12:42:14'), $object->date);
    }
}
