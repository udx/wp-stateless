<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit;

use JsonMapper\Builders\PropertyMapperBuilder;
use JsonMapper\Cache\NullCache;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapper;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\DocBlockAnnotations;
use PHPUnit\Framework\TestCase;

class JsonMapperFactoryTest extends TestCase
{
    /** @covers \JsonMapper\JsonMapperFactory */
    public function testCanCreateCustomMapper(): void
    {
        $factory = new JsonMapperFactory();
        $propertyMapper = PropertyMapperBuilder::new()->build();
        $docBlockMiddleware = new DocBlockAnnotations(new NullCache());

        $mapper = $factory->create($propertyMapper, $docBlockMiddleware);

        self::assertInstanceOf(JsonMapper::class, $mapper);
    }

    /** @covers \JsonMapper\JsonMapperFactory */
    public function testCanCreateDefaultMapper(): void
    {
        $factory = new JsonMapperFactory();

        $mapper = $factory->default();

        self::assertInstanceOf(JsonMapper::class, $mapper);
    }

    /** @covers \JsonMapper\JsonMapperFactory */
    public function testCanCreateBestFitMapper(): void
    {
        $factory = new JsonMapperFactory();

        $mapper = $factory->bestFit();

        self::assertInstanceOf(JsonMapper::class, $mapper);
    }

    /** @covers \JsonMapper\JsonMapperFactory */
    public function testCanCreateMapperWithProvidedJsonMapperBuilder(): void
    {
        $builder = JsonMapperBuilder::new();
        $builder->withJsonMapperClassName(\JsonMapper\Tests\Implementation\JsonMapper::class);
        $factory = new JsonMapperFactory($builder);

        $mapper = $factory->bestFit();

        self::assertInstanceOf(\JsonMapper\Tests\Implementation\JsonMapper::class, $mapper);
    }
}
