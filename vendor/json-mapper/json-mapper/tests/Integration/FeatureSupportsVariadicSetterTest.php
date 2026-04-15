<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\Popo;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsVariadicSetterTest extends TestCase
{
    public function testItCanMapAnArrayUsingAVariadicSetter(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var Popo[] */
            private $popos;

            public function setPopos(Popo ...$popos): void
            {
                $this->popos = $popos;
            }

            public function getPopos(): array
            {
                return $this->popos;
            }
        };
        $json = (object) ['popos' => [(object) ['name' => 'one'], (object) ['name' => 'two']]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals($object->getPopos()[0]->name, $json->popos[0]->name);
        self::assertEquals($object->getPopos()[1]->name, $json->popos[1]->name);
    }
}
