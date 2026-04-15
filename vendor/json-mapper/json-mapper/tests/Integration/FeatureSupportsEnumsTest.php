<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\Php81;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsEnumsTest extends TestCase
{
    /**
     * @requires PHP >= 8.1
     */
    public function testItCanMapAnEnumType(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Php81\BlogPost();
        $json = (object) ['status' => 'draft'];

        $mapper->mapObject($json, $object);

        self::assertSame(Php81\Status::DRAFT, $object->status);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testItCanMapToAnArrayOfEnumType(): void
    {
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var Php81\Status[] */
            public $states;
        };
        $json = (object) ['states' => ['draft', 'archived']];

        $mapper->mapObject($json, $object);

        self::assertSame([Php81\Status::DRAFT, Php81\Status::ARCHIVED], $object->states);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testItCanMapToAnArrayOfEnumTypeWithArrayTypeHint(): void
    {
        $mapper = (new JsonMapperFactory())->bestFit();
        $json = (object) ['states' => ['draft', 'archived']];

        $object = $mapper->mapToClass($json, Php81\GroupOfStatuses::class);

        self::assertSame([Php81\Status::DRAFT, Php81\Status::ARCHIVED], $object->states);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testItCanMapToAMultiDimensionalArrayOfEnumType(): void
    {
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var Php81\Status[][] */
            public $states;
        };
        $json = (object) ['states' => [['draft', 'archived'], ['published']]];

        $mapper->mapObject($json, $object);

        self::assertSame([[Php81\Status::DRAFT, Php81\Status::ARCHIVED], [Php81\Status::PUBLISHED]], $object->states);
    }
}
