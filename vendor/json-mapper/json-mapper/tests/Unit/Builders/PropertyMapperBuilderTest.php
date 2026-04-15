<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Builders;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Builders\PropertyMapperBuilder;
use JsonMapper\Cache\NullCache;
use JsonMapper\Enums\Visibility;
use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Helpers\StrictScalarCaster;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\JsonMapperFactory;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\DocBlockAnnotations;
use JsonMapper\Tests\Implementation\Models\IShape;
use JsonMapper\Tests\Implementation\Models\ShapeInstanceFactory;
use JsonMapper\Tests\Implementation\Models\Square;
use JsonMapper\Tests\Implementation\Models\UserWithConstructor;
use JsonMapper\Tests\Implementation\Models\Wrappers\IShapeWrapper;
use JsonMapper\Tests\Implementation\SimpleObject;
use JsonMapper\Tests\Implementation\UserWithConstructorParent;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class PropertyMapperBuilderTest extends TestCase
{
    /** @covers \JsonMapper\Builders\PropertyMapperBuilder */
    public function testCanReturnFreshInstance(): void
    {
        $instance = PropertyMapperBuilder::new();
        $otherInstance = PropertyMapperBuilder::new();

        self::assertNotSame($instance, $otherInstance);
    }

    /** @covers \JsonMapper\Builders\PropertyMapperBuilder */
    public function testItCanBuildWithClassFactoryRegistry(): void
    {
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType(UserWithConstructor::class, ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['user' => (object) ['id' => 1234, 'name' => 'John Doe']];
        $object = new UserWithConstructorParent();
        $wrapped = new ObjectWrapper($object);
        $classFactoryRegistry = FactoryRegistry::withNativePhpClassesAdded();
        $classFactoryRegistry->addFactory(
            UserWithConstructor::class,
            static function ($params) {
                return new UserWithConstructor($params->id, $params->name);
            }
        );
        $propertyMapper = PropertyMapperBuilder::new()
            ->withClassFactoryRegistry($classFactoryRegistry)
            ->build();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(new UserWithConstructor(1234, 'John Doe'), $object->user);
    }

    /** @covers \JsonMapper\Builders\PropertyMapperBuilder */
    public function testItCanBuildWithNonInstantiableTypeResolver(): void
    {
        $json = (object) ['shape' => (object) ['type' => 'square', 'width' => 5, 'length' => 6]];
        $object = new IShapeWrapper();
        $wrapped = new ObjectWrapper($object);
        $type = IShape::class;
        $property = PropertyBuilder::new()
            ->setName('shape')
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->addType($type, ArrayInformation::notAnArray())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $nonInstantiableTypeResolver = new FactoryRegistry();
        $nonInstantiableTypeResolver->addFactory(IShape::class, new ShapeInstanceFactory());
        $propertyMapper = PropertyMapperBuilder::new()
            ->withNonInstantiableTypeResolver($nonInstantiableTypeResolver)
            ->build();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(new Square(5, 6), $object->shape);
    }

    /** @covers \JsonMapper\Builders\PropertyMapperBuilder */
    public function testItCanBuildWithScalarCaster(): void
    {
        $json = (object) ['name' => 42];
        $object = new SimpleObject();
        $wrapped = new ObjectWrapper($object);
        $property = PropertyBuilder::new()
            ->setName('name')
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->addType('string', ArrayInformation::notAnArray())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $propertyMapper = PropertyMapperBuilder::new()
            ->withScalarCaster(new StrictScalarCaster())
            ->build();
        $mapper = JsonMapperBuilder::new()->withDocBlockAnnotationsMiddleware()->build();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Expected type string, type integer given');
        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $mapper);
    }
}
