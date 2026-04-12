<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware\Constructor;

use JsonMapper\Cache\NullCache;
use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\Constructor\Constructor;
use JsonMapper\Middleware\DocBlockAnnotations;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\Php81\BlogPostWithConstructor;
use JsonMapper\Tests\Implementation\Php81\Status;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\Tests\Implementation\PopoArrayLtGt;
use JsonMapper\Tests\Implementation\PopoContainer;
use JsonMapper\Tests\Implementation\PopoList;
use JsonMapper\Tests\Implementation\PopoWrapperWithConstructor;
use JsonMapper\Tests\Implementation\SimpleObject;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class ConstructorTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     */
    public function testItCanHandleClassWithoutConstructor(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $object = new ComplexObject();
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertFalse($factoryRegistry->hasFactory(get_class($object)));
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     */
    public function testItCanHandleClassWithConstructorWithOptionalArgument(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $json = (object) ['name' => 'John Doe'];
        $object = new SimpleObject();
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($factoryRegistry->hasFactory(get_class($object)));
        self::assertEquals(new SimpleObject($json->name), $factoryRegistry->create(get_class($object), $json));
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     */
    public function testItCanHandleClassTwice(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $json = (object) ['name' => 'John Doe'];
        $object = new class {
            public function __construct(int $value = 0)
            {
            }
        };
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $jsonMapper);
        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($factoryRegistry->hasFactory(get_class($object)));
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     */
    public function testItCanHandleClassWithConstructorWithChildObject(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $json = (object) ['popo' => (object) ['name' => 'Jane Doe']];
        $object = new PopoWrapperWithConstructor(new Popo());
        $propertyMap = $this->getPropertyMapFor($object);
        $jsonMapper = JsonMapperBuilder::new()->withDocBlockAnnotationsMiddleware()->build();
        $popo = new Popo();
        $popo->name = $json->popo->name;
        $expected = new PopoWrapperWithConstructor($popo);

        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($factoryRegistry->hasFactory(get_class($object)));
        self::assertEquals($expected, $factoryRegistry->create(get_class($object), $json));
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     */
    public function testItCanHandleClassWithConstructorWithArrays(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $json = (object) ['items' => [(object) ['name' => 'Jane Doe'], (object) ['name' => 'John Doe']]];
        $object = new PopoContainer([]);
        $propertyMap = $this->getPropertyMapFor($object);
        $jsonMapper = JsonMapperBuilder::new()->withDocBlockAnnotationsMiddleware()->build();
        $popoOne = new Popo();
        $popoOne->name = $json->items[0]->name;
        $popoTwo = new Popo();
        $popoTwo->name = $json->items[1]->name;
        $expected = new PopoContainer([$popoOne, $popoTwo]);

        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($factoryRegistry->hasFactory(get_class($object)));
        self::assertEquals($expected, $factoryRegistry->create(get_class($object), $json));
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     * @requires PHP >= 8.1
     */
    public function testItCanHandleClassWithConstructorHavingEnum(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $json = (object) ['status' => 'published'];
        $object = new BlogPostWithConstructor(Status::PUBLISHED);
        $propertyMap = $this->getPropertyMapFor($object);
        $jsonMapper = JsonMapperBuilder::new()->withDocBlockAnnotationsMiddleware()->build();

        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($factoryRegistry->hasFactory(get_class($object)));
        self::assertEquals($object, $factoryRegistry->create(get_class($object), $json));
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     */
    public function testItCanHandleClassWithConstructorHavingScalarMismatch(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $json = (object) ['name' => 1234];
        $object = new SimpleObject('1234');
        $propertyMap = $this->getPropertyMapFor($object);
        $jsonMapper = JsonMapperBuilder::new()->withDocBlockAnnotationsMiddleware()->build();

        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($factoryRegistry->hasFactory(get_class($object)));
        self::assertEquals($object, $factoryRegistry->create(get_class($object), $json));
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     */
    public function testItCanHandleClassWithMissingParameterConstructor(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $json = (object) [];
        $object = new SimpleObject();
        $propertyMap = $this->getPropertyMapFor($object);
        $jsonMapper = JsonMapperBuilder::new()->withDocBlockAnnotationsMiddleware()->build();

        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($factoryRegistry->hasFactory(get_class($object)));
        self::assertEquals($object, $factoryRegistry->create(get_class($object), $json));
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     */
    public function testItCanHandleClassWithoutNativeTypehint(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $json = (object) [];
        $object = new class {
            /** @var string */
            private $name;

            public function __construct($name = '')
            {
                $this->name = (string) $name;
            }

            public function getName(): string
            {
                return $this->name;
            }
        };
        $jsonMapper = JsonMapperBuilder::new()->withDocBlockAnnotationsMiddleware()->build();

        $middleware->handle($json, new ObjectWrapper($object), new PropertyMap(), $jsonMapper);

        self::assertTrue($factoryRegistry->hasFactory(get_class($object)));
        self::assertEquals($object, $factoryRegistry->create(get_class($object), $json));
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     */
    public function testItCanHandleClassWithConstructorWithArrayLtGt(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $json = (object) ['items' => [(object) ['name' => 'Jane Doe'], (object) ['name' => 'John Doe']]];

        $object = new PopoArrayLtGt([]);
        $propertyMap = $this->getPropertyMapFor($object);
        $jsonMapper = JsonMapperBuilder::new()->withDocBlockAnnotationsMiddleware()->build();
        $popoOne = new Popo();
        $popoOne->name = $json->items[0]->name;
        $popoTwo = new Popo();
        $popoTwo->name = $json->items[1]->name;
        $expected = new PopoArrayLtGt([$popoOne, $popoTwo]);

        $middleware->handle($json, new ObjectWrapper($expected), $propertyMap, $jsonMapper);

        self::assertTrue($factoryRegistry->hasFactory(get_class($object)));
        self::assertEquals($expected, $factoryRegistry->create(get_class($object), $json));
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\Constructor
     */
    public function testItCanHandleClassWithConstructorWithList(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $middleware = new Constructor($factoryRegistry);
        $json = (object) ['items' => [(object) ['name' => 'Jane Doe'], (object) ['name' => 'John Doe']]];

        $object = new PopoList([]);
        $propertyMap = $this->getPropertyMapFor($object);
        $jsonMapper = JsonMapperBuilder::new()->withDocBlockAnnotationsMiddleware()->build();
        $popoOne = new Popo();
        $popoOne->name = $json->items[0]->name;
        $popoTwo = new Popo();
        $popoTwo->name = $json->items[1]->name;
        $expected = new PopoList([$popoOne, $popoTwo]);

        $middleware->handle($json, new ObjectWrapper($expected), $propertyMap, $jsonMapper);

        self::assertTrue($factoryRegistry->hasFactory(get_class($object)));
        self::assertEquals($expected, $factoryRegistry->create(get_class($object), $json));
    }

    private function getPropertyMapFor($object): PropertyMap
    {
        $propertyMap = new PropertyMap();
        $docBlock = new DocBlockAnnotations(new NullCache());
        $docBlock->handle(
            new \stdClass(),
            new ObjectWrapper($object),
            $propertyMap,
            $this->createMock(JsonMapperInterface::class)
        );

        return $propertyMap;
    }
}
