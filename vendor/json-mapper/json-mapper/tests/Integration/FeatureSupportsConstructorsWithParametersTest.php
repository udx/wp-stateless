<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\Tests\Implementation\Php81\Foo\FooCollection;
use JsonMapper\Tests\Implementation\Php81\WithConstructorPropertyPromotion;
use JsonMapper\Tests\Implementation\Php81\WithConstructorReadOnlyDateTimePropertyPromotion;
use JsonMapper\Tests\Implementation\Php81\WithConstructorReadOnlyPropertyCollection;
use JsonMapper\Tests\Implementation\Php81\WithConstructorReadOnlyPropertyPromotion;
use JsonMapper\Tests\Implementation\Php81\WithConstructorReadOnlyPropertySimple;
use JsonMapper\Tests\Implementation\Php81\WrapperWithConstructorReadOnlyPropertyPromotion;
use JsonMapper\Tests\Implementation\PopoWrapperWithConstructor;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsConstructorsWithParametersTest extends TestCase
{
    public function testCanHandleCustomConstructors(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $mapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->build();

        $json = (object) [
            'popo' => (object) [
                'name' => 'John Doe'
            ]
        ];

        $result = $mapper->mapToClass($json, PopoWrapperWithConstructor::class);

        self::assertInstanceOf(PopoWrapperWithConstructor::class, $result);
        self::assertEquals($json->popo->name, $result->getPopo()->name);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testCanHandleCustomConstructorsWithPropertyPromotion(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $mapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->build();

        $json = (object) [
            'value' => 'John Doe',
        ];

        $result = $mapper->mapToClass($json, WithConstructorPropertyPromotion::class);

        self::assertInstanceOf(WithConstructorPropertyPromotion::class, $result);
        self::assertEquals($json->value, $result->getValue());
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testCanHandleCustomConstructorsWithReadonlyPropertyPromotion(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $mapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->build();

        $json = (object) [
            'value' => 'John Doe',
        ];

        $result = $mapper->mapToClass($json, WithConstructorReadOnlyPropertyPromotion::class);

        self::assertInstanceOf(WithConstructorReadOnlyPropertyPromotion::class, $result);
        self::assertEquals($json->value, $result->value);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testCanHandleCustomConstructorsWithNestedCustomConstructorReadonlyPropertyPromotion(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $mapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->build();

        $json = (object) [
            'value' => (object) [
                'value' => 'John Doe',
            ],
        ];

        $result = $mapper->mapToClass($json, WrapperWithConstructorReadOnlyPropertyPromotion::class);

        self::assertInstanceOf(WrapperWithConstructorReadOnlyPropertyPromotion::class, $result);
        self::assertEquals($json->value->value, $result->value->value);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testCanHandleCustomConstructorsWithReadonlyDateTimePropertyPromotion(): void
    {
        $factoryRegistry = FactoryRegistry::withNativePhpClassesAdded();
        $jsonMapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->build();
        $json = (object) [
            'date' => '1987-10-03T14:14:32+01:00',
        ];

        $result = $jsonMapper->mapToClass($json, WithConstructorReadOnlyDateTimePropertyPromotion::class);

        self::assertInstanceOf(WithConstructorReadOnlyDateTimePropertyPromotion::class, $result);
        self::assertInstanceOf(\DateTimeImmutable::class, $result->date);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testCanHandleCustomConstructorsWithEmptyArray(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $mapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->build();

        $json = (object) [
            'simples' => [],
        ];

        $result = $mapper->mapToClass($json, WithConstructorReadOnlyPropertyCollection::class);

        self::assertInstanceOf(WithConstructorReadOnlyPropertyCollection::class, $result);
        self::assertIsArray($result->simples);
        self::assertEmpty($result->simples);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testCanHandleCustomConstructorsWithArray(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $mapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->build();

        $status = 5;
        $json = (object) [
            'simples' => [(object) ['status' => $status]],
        ];

        $result = $mapper->mapToClass($json, WithConstructorReadOnlyPropertyCollection::class);

        self::assertInstanceOf(WithConstructorReadOnlyPropertyCollection::class, $result);
        self::assertIsArray($result->simples);
        self::assertInstanceOf(WithConstructorReadOnlyPropertySimple::class, $result->simples[0]);
        self::assertEquals($status, $result->simples[0]->status);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testHandleCollectionMapping(): void
    {
        $factoryRegistry = FactoryRegistry::withNativePhpClassesAdded();
        $propertyMapper = new PropertyMapper($factoryRegistry);
        $jsonMapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->withPropertyMapper($propertyMapper)
            ->build();

        $json = (object) ['items' => [
            (object) ['name' => 'foo', 'orders' => [(object) ['name' => 'bar']]],
            (object) ['name' => 'foo', 'orders' => [(object) ['name' => 'bar']]],
        ]];

        $result = $jsonMapper->mapToClass($json, FooCollection::class);

        static::assertInstanceOf(FooCollection::class, $result);
    }
}
