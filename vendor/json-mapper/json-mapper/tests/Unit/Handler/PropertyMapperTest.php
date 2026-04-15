<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Handler;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Cache\NullCache;
use JsonMapper\Enums\Visibility;
use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperFactory;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\DocBlockAnnotations;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\Models\IShape;
use JsonMapper\Tests\Implementation\Models\ShapeInstanceFactory;
use JsonMapper\Tests\Implementation\Models\Square;
use JsonMapper\Tests\Implementation\Models\User;
use JsonMapper\Tests\Implementation\Models\UserWithConstructor;
use JsonMapper\Tests\Implementation\Models\Wrappers\IShapeWrapper;
use JsonMapper\Tests\Implementation\Php81\BlogPost;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\Tests\Implementation\PrivatePropertyWithoutSetter;
use JsonMapper\Tests\Implementation\SimpleObject;
use JsonMapper\Tests\Implementation\UserWithConstructorParent;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class PropertyMapperTest extends TestCase
{
    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testAdditionalJsonIsIgnored(): void
    {
        $propertyMapper = new PropertyMapper();
        $json = (object) ['file' => __FILE__];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);

        $propertyMapper->__invoke($json, $wrapped, new PropertyMap(), $this->createMock(JsonMapperInterface::class));

        self::assertEquals(new \stdClass(), $object);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     * @dataProvider scalarValueDataTypes
     * @param mixed $value
     */
    public function testPublicScalarValueIsSet(string $type, $value): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType($type, ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['value' => $value];
        $object = new class {
            /** @var mixed */
            public $value;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $this->createMock(JsonMapperInterface::class));

        self::assertEquals($value, $object->value);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicBuiltinClassIsSet(): void
    {
        $property = PropertyBuilder::new()
            ->setName('createdAt')
            ->addType(\DateTimeImmutable::class, ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $now = new \DateTimeImmutable();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['createdAt' => $now->format('Y-m-d\TH:i:s.uP')];
        $object = new class {
            /** @var \DateTimeImmutable */
            public $createdAt;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $this->createMock(JsonMapperInterface::class));

        self::assertEquals($now, $object->createdAt);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicCustomClassIsSet(): void
    {
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType(SimpleObject::class, ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $jsonMapper->expects(self::once())
            ->method('mapToClass')
            ->with((object) ['name' => __FUNCTION__], SimpleObject::class)
            ->willReturnCallback(static function (\stdClass $json, string $className) {
                $object = new $className();
                $object->setName($json->name);

                return $object;
            });
        $json = (object) ['child' => (object) ['name' => __FUNCTION__]];
        $object = new ComplexObject();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        $child = $object->getChild();
        self::assertNotNull($child);
        self::assertEquals(__FUNCTION__, $child->getName());
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicScalarValueArrayIsSet(): void
    {
        $fileProperty = PropertyBuilder::new()
            ->setName('ids')
            ->addType('int', ArrayInformation::singleDimension())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($fileProperty);
        $json = (object) ['ids' => [1, 2, 3]];
        $object = new class {
            /** @var int[] */
            public $ids;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $this->createMock(JsonMapperInterface::class));

        self::assertEquals([1, 2, 3], $object->ids);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicScalarValueMultiDimensionalArrayIsSet(): void
    {
        $fileProperty = PropertyBuilder::new()
            ->setName('ids')
            ->addType('int', ArrayInformation::multiDimension(2))
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($fileProperty);
        $value = [1 => [2, 3], 2 => [2, 3], 3 => [2, 3]];
        $json = (object) ['ids' => $value];
        $object = new class {
            /** @var int[][] */
            public $ids;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $this->createMock(JsonMapperInterface::class));

        self::assertEquals($value, $object->ids);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicCustomClassArrayIsSet(): void
    {
        $property = PropertyBuilder::new()
            ->setName('children')
            ->addType(SimpleObject::class, ArrayInformation::singleDimension())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $jsonMapper->expects(self::exactly(2))
            ->method('mapToClass')
            ->with((object) ['name' => __FUNCTION__], SimpleObject::class)
            ->willReturnCallback(static function (\stdClass $json, string $className) {
                $object = new $className();
                $object->setName($json->name);

                return $object;
            });
        $json = (object) ['children' => [(object) ['name' => __FUNCTION__], (object) ['name' => __FUNCTION__]]];
        $object = new ComplexObject();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertCount(2, $object->getChildren());
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicCustomClassMultidimensionalArrayIsSet(): void
    {
        $property = PropertyBuilder::new()
            ->setName('children')
            ->addType(SimpleObject::class, ArrayInformation::multiDimension(2))
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $jsonMapper->expects(self::exactly(4))
            ->method('mapToClass')
            ->with(self::isInstanceOf(\stdClass::class), SimpleObject::class)
            ->willReturnCallback(static function ($json, string $className) {
                $object = new $className();
                $object->setName($json->name);

                return $object;
            });
        $json = (object) ['children' => [
            [
                (object) ['name' => __NAMESPACE__],
                (object) ['name' => __FUNCTION__]
            ],
            [
                (object) ['name' => __NAMESPACE__],
                (object) ['name' => __FUNCTION__]
            ],
        ]];
        $object = new class {
            /** @var SimpleObject[]  */
            public $children;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertCount(2, $object->children);
        self::assertEquals(
            [
                [new SimpleObject(__NAMESPACE__), new SimpleObject(__FUNCTION__)],
                [new SimpleObject(__NAMESPACE__), new SimpleObject(__FUNCTION__)],
            ],
            $object->children
        );
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testArrayPropertyIsCasted(): void
    {
        $property = PropertyBuilder::new()
            ->setName('notes')
            ->addType('string', ArrayInformation::singleDimension())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['notes' => (object) ['note_one' => __FUNCTION__, 'note_two' => __CLASS__]];
        $object = new Popo();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(['note_one' => __FUNCTION__, 'note_two' => __CLASS__], $object->notes);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testCanMapPropertyWithClassFactory(): void
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
        $propertyMapper = new PropertyMapper($classFactoryRegistry);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(new UserWithConstructor(1234, 'John Doe'), $object->user);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testCanMapPropertyAsArrayWithClassFactory(): void
    {
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType(UserWithConstructor::class, ArrayInformation::singleDimension())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['user' => [
            0 => (object) ['id' => 1234, 'name' => 'John Doe'],
            1 => (object) ['id' => 5678, 'name' => 'Jane Doe']
        ]];
        $object = new UserWithConstructorParent();
        $wrapped = new ObjectWrapper($object);
        $classFactoryRegistry = FactoryRegistry::withNativePhpClassesAdded();
        $classFactoryRegistry->addFactory(
            UserWithConstructor::class,
            static function ($params) {
                return new UserWithConstructor($params->id, $params->name);
            }
        );
        $propertyMapper = new PropertyMapper($classFactoryRegistry);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(
            [new UserWithConstructor(1234, 'John Doe'), new UserWithConstructor(5678, 'Jane Doe')],
            $object->user
        );
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testCanMapPropertyAsMultiDimensionalArrayWithClassFactory(): void
    {
        $property = PropertyBuilder::new()
            ->setName('userHistory')
            ->addType(UserWithConstructor::class, ArrayInformation::multiDimension(2))
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['userHistory' => [
            '2021-02-03' => [
                'original' => (object) ['id' => 1234, 'name' => 'John Doe'],
                'new' => (object) ['id' => 1234, 'name' => 'Johnathan Doe'],
            ],
            '2022-08-16' => [
                'original' => (object) ['id' => 1234, 'name' => 'Johnathan Doe'],
                'new' => (object) ['id' => 1234, 'name' => 'J. Doe'],
            ],
        ]];
        $object = new UserWithConstructorParent();
        $wrapped = new ObjectWrapper($object);
        $classFactoryRegistry = FactoryRegistry::withNativePhpClassesAdded();
        $classFactoryRegistry->addFactory(
            UserWithConstructor::class,
            static function ($params) {
                return new UserWithConstructor($params->id, $params->name);
            }
        );
        $propertyMapper = new PropertyMapper($classFactoryRegistry);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(
            [
                '2021-02-03' => [
                    'original' => new UserWithConstructor(1234, 'John Doe'),
                    'new' => new UserWithConstructor(1234, 'Johnathan Doe'),
                ],
                '2022-08-16' => [
                    'original' => new UserWithConstructor(1234, 'Johnathan Doe'),
                    'new' => new UserWithConstructor(1234, 'J. Doe'),
                ],
            ],
            $object->userHistory
        );
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testCanMapUnionPropertyAsArrayWithClassFactory(): void
    {
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType(UserWithConstructor::class, ArrayInformation::singleDimension())
            ->addType(\DateTime::class, ArrayInformation::singleDimension())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['user' => [
            0 => (object) ['id' => 1234, 'name' => 'John Doe'],
            1 => (object) ['id' => 5678, 'name' => 'Jane Doe']
        ]];
        $object = new UserWithConstructorParent();
        $wrapped = new ObjectWrapper($object);
        $classFactoryRegistry = FactoryRegistry::withNativePhpClassesAdded();
        $classFactoryRegistry->addFactory(
            UserWithConstructor::class,
            static function ($params) {
                return new UserWithConstructor($params->id, $params->name);
            }
        );
        $propertyMapper = new PropertyMapper($classFactoryRegistry);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(
            [new UserWithConstructor(1234, 'John Doe'), new UserWithConstructor(5678, 'Jane Doe')],
            $object->user
        );
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testWillSetNullOnNullablePropertyIfNullProvided(): void
    {
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType(SimpleObject::class, ArrayInformation::notAnArray())
            ->setIsNullable(true)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['child' => null];
        $object = new ComplexObject();
        $object->setChild(new SimpleObject());
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertNull($object->getChild());
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicNotNullableCustomClassThrowsException(): void
    {
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType(SimpleObject::class, ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['child' => null];
        $object = new ComplexObject();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $this->expectException(\RuntimeException::class);
        $message = "Null provided in json where " . ComplexObject::class . "::child doesn't allow null value";
        $this->expectExceptionMessage($message);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testNonPublicPropertyWithoutSetterThrowsException(): void
    {
        $property = PropertyBuilder::new()
            ->setName('number')
            ->addType('int', ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['number' => 42];
        $object = new PrivatePropertyWithoutSetter();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $this->expectException(\RuntimeException::class);
        $message = PrivatePropertyWithoutSetter::class . "::number is non-public and no setter method was found";
        $this->expectExceptionMessage($message);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     * @dataProvider scalarValueDataTypes
     * @param mixed $value
     */
    public function testItCanMapAScalarUnionType(string $type, $value): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType('int', ArrayInformation::notAnArray())
            ->addType('double', ArrayInformation::notAnArray())
            ->addType('float', ArrayInformation::notAnArray())
            ->addType('string', ArrayInformation::notAnArray())
            ->addType('bool', ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['value' => $value];
        $object = new class {
            /** @var int|double|float|string|bool */
            public $value;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($value, $object->value);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     * @dataProvider scalarValueDataTypes
     * @param mixed $value
     */
    public function testItCanMapAnArrayOfScalarUnionType(string $type, $value): void
    {
        $property = PropertyBuilder::new()
            ->setName('values')
            ->addType('int', ArrayInformation::singleDimension())
            ->addType('float', ArrayInformation::singleDimension())
            ->addType('string', ArrayInformation::singleDimension())
            ->addType('bool', ArrayInformation::singleDimension())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['values' => [$value]];
        $object = new class {
            /** @var int[]|float[]|string[]|bool[] */
            public $values;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals([$value], $object->values);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapAUnionOfUnixTimeStampAndDateTimeWithDateTimeObject(): void
    {
        $now = new \DateTime();
        $property = PropertyBuilder::new()
            ->setName('moment')
            ->addType('int', ArrayInformation::notAnArray())
            ->addType(\DateTime::class, ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['moment' => $now->format('Y-m-d\TH:i:s.uP')];
        $object = new class {
            /** @var int|\DateTime */
            public $moment;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($now, $object->moment);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapAUnionOfCustomClasses(): void
    {
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType(User::class, ArrayInformation::notAnArray())
            ->addType(Popo::class, ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['user' => (object) ['id' => 42, 'name' => 'John Doe']];
        $object = new class {
            /** @var User|Popo */
            public $user;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($json->user->id, $object->user->getId());
        self::assertEquals($json->user->name, $object->user->getName());
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapAUnionOfCustomClassesAsArray(): void
    {
        $property = PropertyBuilder::new()
            ->setName('users')
            ->addType(User::class, ArrayInformation::singleDimension())
            ->addType(Popo::class, ArrayInformation::singleDimension())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['users' => [0 => (object) ['id' => 42, 'name' => 'John Doe']]];
        $object = new class {
            /** @var User[]|Popo[] */
            public $users;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($json->users[0]->id, $object->users[0]->getId());
        self::assertEquals($json->users[0]->name, $object->users[0]->getName());
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapIfNoTypeDetailIsAvailable(): void
    {
        $property = PropertyBuilder::new()
            ->setName('id')
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['id' => 42];
        $object = new class {
            public $id;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($json->id, $object->id);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapUsingAVariadicSetterFunction(): void
    {
        $property = PropertyBuilder::new()
            ->setName('numbers')
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['numbers' => [1, 2, 3, 4, 5]];
        $object = new class {
            /** @var int[] */
            private $numbers;

            public function getNumbers(): array
            {
                return $this->numbers;
            }

            public function setNumbers(int ...$numbers): void
            {
                $this->numbers = $numbers;
            }
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals([1, 2, 3, 4, 5], $object->getNumbers());
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItThrowsAnExceptionWhenInterfaceTypeCantBeCreated(): void
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

        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to resolve un-instantiable {$type} as no factory was registered");
        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapInterfaceType(): void
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

        $propertyMapper = new PropertyMapper(null, $nonInstantiableTypeResolver);
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(new Square(5, 6), $object->shape);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     * @requires PHP >= 8.1
     */
    public function testItCanMapEnumType(): void
    {
        $json = (object) ['status' => 'draft'];
        $object = new \JsonMapper\Tests\Implementation\Php81\BlogPost();
        $wrapped = new ObjectWrapper($object);
        $property = PropertyBuilder::new()
            ->setName('status')
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->addType(\JsonMapper\Tests\Implementation\Php81\Status::class, ArrayInformation::notAnArray())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $expected = new BlogPost();
        $expected->status = \JsonMapper\Tests\Implementation\Php81\Status::DRAFT;

        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($expected, $object);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     * @requires PHP >= 8.1
     */
    public function testItCanMapEnumArrayType(): void
    {
        $json = (object) ['historicStates' => ['draft', 'published']];
        $object = new \JsonMapper\Tests\Implementation\Php81\BlogPost();
        $wrapped = new ObjectWrapper($object);
        $property = PropertyBuilder::new()
            ->setName('historicStates')
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->addType(\JsonMapper\Tests\Implementation\Php81\Status::class, ArrayInformation::singleDimension())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $expected = new BlogPost();
        $expected->historicStates = [
            \JsonMapper\Tests\Implementation\Php81\Status::DRAFT,
            \JsonMapper\Tests\Implementation\Php81\Status::PUBLISHED
        ];

        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($expected, $object);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     * @requires PHP >= 8.1
     */
    public function testItCanMapEnumMultiDimensionalArrayType(): void
    {
        $json = (object) ['historicStatesByDate' => [
            '2022-08-16' => ['draft', 'published'],
            '2022-08-22' => ['archived']
        ]];
        $object = new \JsonMapper\Tests\Implementation\Php81\BlogPost();
        $wrapped = new ObjectWrapper($object);
        $property = PropertyBuilder::new()
            ->setName('historicStatesByDate')
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->addType(\JsonMapper\Tests\Implementation\Php81\Status::class, ArrayInformation::multiDimension(2))
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $expected = new BlogPost();
        $expected->historicStatesByDate = [
            '2022-08-16' => [
                \JsonMapper\Tests\Implementation\Php81\Status::DRAFT,
                \JsonMapper\Tests\Implementation\Php81\Status::PUBLISHED,
            ],
            '2022-08-22' => [
                \JsonMapper\Tests\Implementation\Php81\Status::ARCHIVED,
            ]
        ];

        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($expected, $object);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItThrowsExceptionForNonExistingClass(): void
    {
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType("\Some\Non\Existing\Class", ArrayInformation::notAnArray())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['child' => (object) ['name' => __FUNCTION__]];
        $object = new ComplexObject();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to map to \Some\Non\Existing\Class');
        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapAnEmptyArrayForArrayType(): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType('string', ArrayInformation::notAnArray())
            ->addType('string', ArrayInformation::singleDimension())
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['value' => []];
        $object = new class {
            /** @var string|string[] */
            public $value;
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals([], $object->value);
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
}
