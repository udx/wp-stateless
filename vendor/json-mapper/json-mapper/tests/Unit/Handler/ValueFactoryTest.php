<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Handler;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Cache\NullCache;
use JsonMapper\Enums\Visibility;
use JsonMapper\Exception\ClassFactoryException;
use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\Handler\ValueFactory;
use JsonMapper\Helpers\ScalarCaster;
use JsonMapper\JsonMapperFactory;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\DocBlockAnnotations;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\Models\Circle as Circle;
use JsonMapper\Tests\Implementation\Models\IShape;
use JsonMapper\Tests\Implementation\Models\ShapeInstanceFactory;
use JsonMapper\Tests\Implementation\Models\Square;
use JsonMapper\Tests\Implementation\Models\User;
use JsonMapper\Tests\Implementation\Models\UserWithConstructor;
use JsonMapper\Tests\Implementation\Models\Wrappers\IShapeAware;
use JsonMapper\Tests\Implementation\Models\Wrappers\IShapeWrapper;
use JsonMapper\Tests\Implementation\Php81\BlogPost;
use JsonMapper\Tests\Implementation\Php81\Status;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\Tests\Implementation\PrivatePropertyWithoutSetter;
use JsonMapper\Tests\Implementation\SimpleObject;
use JsonMapper\Tests\Implementation\UserWithConstructorParent;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class ValueFactoryTest extends TestCase
{
    /**
     * @covers \JsonMapper\Handler\ValueFactory
     */
    public function testItCanMapToAnEmptyArrayForUnionProperty(): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType('integer', ArrayInformation::singleDimension())
            ->addType('string', ArrayInformation::singleDimension())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());

        $buildValue = $valueFactory->build($jsonMapper, $property, []);

        self::assertEquals([], $buildValue);
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @dataProvider combinationsOfScalarValueDataTypeAndArrayInformation
     * @param mixed $value
     */
    public function testItCanMapPropertyWithoutTypeInfo(string $type, $value): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals($value, $buildValue);
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @dataProvider combinationsOfScalarValueDataTypeAndArrayInformation
     * @param mixed $value
     */
    public function testItCanMapScalarPropertyWithSingleType(
        string $type,
        $value,
        ArrayInformation $arrayInformation
    ): void {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType($type, $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals($value, $buildValue);
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @requires PHP >= 8.1
     * @dataProvider arrayInformationDataProvider
     */
    public function testItCanMapEnumPropertyWithSingleType(ArrayInformation $arrayInformation): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType(Status::class, $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());
        $value = $this->wrapValueWithArrayInformation('archived', $arrayInformation);

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals(
            $this->wrapValueWithArrayInformation(Status::from('archived'), $arrayInformation),
            $buildValue
        );
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @dataProvider arrayInformationDataProvider
     */
    public function testItCanMapWithClassFactoryHavingAvailableFactoryForASingleType(
        ArrayInformation $arrayInformation
    ): void {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType(\DateTimeImmutable::class, $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(
            new ScalarCaster(),
            FactoryRegistry::withNativePhpClassesAdded(),
            new FactoryRegistry()
        );
        $value = $this->wrapValueWithArrayInformation('2000-01-01T00:00:00', $arrayInformation);

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals(
            $this->wrapValueWithArrayInformation(new \DateTimeImmutable('2000-01-01T00:00:00'), $arrayInformation),
            $buildValue
        );
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @dataProvider arrayInformationDataProvider
     */
    public function testItCanMapToAnObjectUsingMapperForASingleType(ArrayInformation $arrayInformation): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType(SimpleObject::class, $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());
        $value = $this->wrapValueWithArrayInformation((object) ['name' => 'John Doe'], $arrayInformation);
        $jsonMapper->expects($this->once())
            ->method('mapToClass')
            ->with($this->isInstanceOf(\stdClass::class), SimpleObject::class)
            ->willReturnCallback(function ($data) {
                return new SimpleObject($data->name);
            });

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals(
            $this->wrapValueWithArrayInformation(new SimpleObject('John Doe'), $arrayInformation),
            $buildValue
        );
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @dataProvider arrayInformationDataProvider
     */
    public function testItCanMapArrayOfScalarValuesForUnionType(ArrayInformation $arrayInformation): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType('mixed', $arrayInformation)
            ->addType('float', $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());
        $value = $this->wrapValueWithArrayInformation(mt_rand() / mt_getrandmax(), $arrayInformation);

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals($value, $buildValue);
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @requires PHP >= 8.1
     * @dataProvider arrayInformationDataProvider
     */
    public function testItCanMapArrayOfEnumValuesForUnionType(ArrayInformation $arrayInformation): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType(Status::class, $arrayInformation)
            ->addType('integer', $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());
        $value = $this->wrapValueWithArrayInformation('archived', $arrayInformation);

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals(
            $this->wrapValueWithArrayInformation(Status::from('archived'), $arrayInformation),
            $buildValue
        );
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @dataProvider arrayInformationDataProvider
     */
    public function testItCanMapArrayWithClassFactoryHavingAvailableFactoryForUnionTYpe(
        ArrayInformation $arrayInformation
    ): void {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType('integer', $arrayInformation)
            ->addType(\DateTimeImmutable::class, $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(
            new ScalarCaster(),
            FactoryRegistry::withNativePhpClassesAdded(),
            new FactoryRegistry()
        );
        $value = $this->wrapValueWithArrayInformation('2000-01-01T00:00:00', $arrayInformation);

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals(
            $this->wrapValueWithArrayInformation(new \DateTimeImmutable('2000-01-01T00:00:00'), $arrayInformation),
            $buildValue
        );
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @dataProvider arrayInformationDataProvider
     */
    public function testItCanMapArrayWithMapperForUnionType(
        ArrayInformation $arrayInformation
    ): void {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType('integer', $arrayInformation)
            ->addType(Popo::class, $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $jsonMapper->method('mapToClass')
            ->with(self::isInstanceOf(\stdClass::class), Popo::class)
            ->willReturnCallback(function (\stdClass $data, string $className) {
                $popo = new Popo();
                $popo->name = $data->name;

                return $popo;
            });
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());
        $expected = new Popo();
        $expected->name = 'Jane Doe';
        $value = $this->wrapValueWithArrayInformation((object) ['name' => $expected->name], $arrayInformation);

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals(
            $this->wrapValueWithArrayInformation($expected, $arrayInformation),
            $buildValue
        );
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @dataProvider arrayInformationDataProvider
     */
    public function testItCanMapUnInstantiableTypeForSingleType(
        ArrayInformation $arrayInformation
    ): void {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType(IShape::class, $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $jsonMapper->method('mapObject')
            ->with(self::isInstanceOf(\stdClass::class), self::isInstanceOf(Circle::class))
            ->willReturnCallback(function (\stdClass $data, Circle $object) {
                $object->radius = $data->radius;
                return $object;
            });
        $nonInstantiableTypeResolver = new FactoryRegistry();
        $nonInstantiableTypeResolver->addFactory(IShape::class, function (\stdClass $data) {
            if ($data->radius) {
                return new Circle();
            }
        });
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), $nonInstantiableTypeResolver);
        $radius = random_int(1, 12);
        $value = $this->wrapValueWithArrayInformation((object) ['radius' => $radius], $arrayInformation);
        $expected = new Circle();
        $expected->radius = $radius;

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals(
            $this->wrapValueWithArrayInformation($expected, $arrayInformation),
            $buildValue
        );
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @dataProvider arrayInformationDataProvider
     */
    public function testThrowsExceptionForUnInstantiableTypeForSingleTypeThatCanNotBeResolved(
        ArrayInformation $arrayInformation
    ): void {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType(IShape::class, $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $nonInstantiableTypeResolver = new FactoryRegistry();
        $nonInstantiableTypeResolver->addFactory(IShape::class, function () {
            throw new ClassFactoryException();
        });
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), $nonInstantiableTypeResolver);

        $value = $this->wrapValueWithArrayInformation((object) ['radius' => random_int(1, 12)], $arrayInformation);

        $this->expectException(\RuntimeException::class);
        $valueFactory->build($jsonMapper, $property, $value);
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     * @dataProvider arrayInformationDataProvider
     */
    public function testItCanMapArrayWithMapperForSingleType(
        ArrayInformation $arrayInformation
    ): void {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType(Popo::class, $arrayInformation)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $jsonMapper->method('mapToClass')
            ->with(self::isInstanceOf(\stdClass::class), Popo::class)
            ->willReturnCallback(function (\stdClass $data, string $className) {
                $popo = new Popo();
                $popo->name = $data->name;

                return $popo;
            });
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());
        $expected = new Popo();
        $expected->name = 'Jane Doe';
        $value = $this->wrapValueWithArrayInformation((object) ['name' => $expected->name], $arrayInformation);

        $buildValue = $valueFactory->build($jsonMapper, $property, $value);

        self::assertEquals(
            $this->wrapValueWithArrayInformation($expected, $arrayInformation),
            $buildValue
        );
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     */
    public function testItThrowsExceptionForNonExistingClass(): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType('\A\B\C\D\E\F', ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());
        $value = $this->wrapValueWithArrayInformation((object) [], ArrayInformation::notAnArray());

        $this->expectException(\Exception::class);
        $valueFactory->build($jsonMapper, $property, $value);
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     */
    public function testItCanMapToNullWhenPropertyIsNullable(): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType(\DateTimeImmutable::class, ArrayInformation::notAnArray())
            ->setIsNullable(true)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());

        $this->assertNull($valueFactory->build($jsonMapper, $property, null));
    }

    /**
     * @covers \JsonMapper\Handler\ValueFactory
     */
    public function testItCanMapToNullableClassFromUnionProperty(): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType(\DateTimeImmutable::class, ArrayInformation::notAnArray())
            ->addType(\DateTime::class, ArrayInformation::notAnArray())
            ->setIsNullable(true)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $valueFactory = new ValueFactory(new ScalarCaster(), new FactoryRegistry(), new FactoryRegistry());

        $this->assertNull($valueFactory->build($jsonMapper, $property, null));
    }


    public function scalarValueDataTypes(): array
    {
        return [
            'string' => ['string', 'Some string'],
            'boolean' => ['bool', true],
            'integer' => ['int', 1],
            'float' => ['float', M_PI],
            'double' => ['double', M_PI],
        ];
    }

    public function combinationsOfScalarValueDataTypeAndArrayInformation(): array
    {
        $values = [];
        foreach ($this->scalarValueDataTypes() as $key => [$type, $value]) {
            $values[$key . ' as single type'] = [
                $type,
                $value,
                ArrayInformation::notAnArray()
            ];
            $values[$key . ' as single dimension array'] = [
                $type,
                [$value, $value],
                ArrayInformation::singleDimension()
            ];
            $values[$key . ' as multi dimension array'] = [
                $type,
                [[$value], [$value]],
                ArrayInformation::multiDimension(2)
            ];
        }

        return $values;
    }

    public function arrayInformationDataProvider(): array
    {
        return [
            'not an array' => [ArrayInformation::notAnArray()],
            'one dimensional array' => [ArrayInformation::singleDimension()],
            'two dimensional array' => [ArrayInformation::multiDimension(2)],
        ];
    }

    private function wrapValueWithArrayInformation($value, ArrayInformation $arrayInformation)
    {
        if ($arrayInformation->equals(ArrayInformation::singleDimension())) {
            return [$value];
        }
        if ($arrayInformation->equals(ArrayInformation::multiDimension(2))) {
            return [[$value]];
        }

        return $value;
    }
}
