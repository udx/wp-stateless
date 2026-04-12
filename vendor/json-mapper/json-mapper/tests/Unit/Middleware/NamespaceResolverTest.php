<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Cache\NullCache;
use JsonMapper\Enums\Visibility;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\DocBlockAnnotations;
use JsonMapper\Middleware\NamespaceResolver;
use JsonMapper\Tests\Helpers\AssertThatPropertyTrait;
use JsonMapper\Tests\Helpers\CacheKeyHelper;
use JsonMapper\Tests\Implementation\Bar\CustomerState;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\Foo\Customer;
use JsonMapper\Tests\Implementation\Models\NamespaceAliasObject;
use JsonMapper\Tests\Implementation\Models\NamespaceObject;
use JsonMapper\Tests\Implementation\Models\Sub\AnotherValueHolder;
use JsonMapper\Tests\Implementation\Models\User;
use JsonMapper\Tests\Implementation\Models\ValueHolder;
use JsonMapper\Tests\Implementation\SimpleObject;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class NamespaceResolverTest extends TestCase
{
    use AssertThatPropertyTrait;

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItResolvesNamespacesForImportedNamespace(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new ComplexObject();
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType('User', ArrayInformation::notAnArray())
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('user'));
        $this->assertThatProperty($propertyMap->getProperty('user'))
            ->onlyHasType(User::class, ArrayInformation::notAnArray());
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItResolvesNamespacesWithinSameNamespace(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new ComplexObject();
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType('SimpleObject', ArrayInformation::notAnArray())
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('child'));
        $this->assertThatProperty($propertyMap->getProperty('child'))
            ->onlyHasType(SimpleObject::class, ArrayInformation::notAnArray());
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItDoesntApplyResolvingToScalarTypes(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new SimpleObject();
        $property = PropertyBuilder::new()
            ->setName('name')
            ->addType('string', ArrayInformation::notAnArray())
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('name'));
        $this->assertThatProperty($propertyMap->getProperty('name'))
            ->onlyHasType('string', ArrayInformation::notAnArray());
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItDoesntApplyResolvingToFullyQualifiedClassName(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new SimpleObject();
        $property = PropertyBuilder::new()
            ->setName('name')
            ->addType(__CLASS__, ArrayInformation::notAnArray())
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('name'));
        $this->assertThatProperty($propertyMap->getProperty('name'))
            ->onlyHasType(__CLASS__, ArrayInformation::notAnArray());
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItResolvesNamespacesForImportedNamespaceWithArray(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new ComplexObject();
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType('User', ArrayInformation::singleDimension())
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('user'));
        $this->assertThatProperty($propertyMap->getProperty('user'))
            ->onlyHasType(User::class, ArrayInformation::singleDimension());
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItResolvesNamespacesWithinSameNamespaceWithArray(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new ComplexObject();
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType('SimpleObject', ArrayInformation::singleDimension())
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('child'));
        $this->assertThatProperty($propertyMap->getProperty('child'))
            ->onlyHasType(SimpleObject::class, ArrayInformation::singleDimension());
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testReturnsFromCacheWhenAvailable(): void
    {
        $propertyMap = new PropertyMap();
        $objectWrapper = $this->createMock(ObjectWrapper::class);
        $objectWrapper->method('getName')->willReturn(__METHOD__);
        $objectWrapper->expects(self::never())->method('getReflectedObject');
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')
            ->with(Assert::stringContains(CacheKeyHelper::sanatize(__METHOD__)))
            ->willReturn(true);
        $cache->method('get')
            ->with(Assert::stringContains(CacheKeyHelper::sanatize(__METHOD__)))
            ->willReturn($propertyMap);
        $middleware = new NamespaceResolver($cache);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), $objectWrapper, $propertyMap, $jsonMapper);
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testReturnsCorrectNamespaceWhenOtherClassHasPartialMatch(): void
    {
        $object = new NamespaceObject();
        $input = (object) [
            'valueHolder' => (object) ['value' => 'loremipsum1'],
            'anotherValueHolder' => (object) ['value' => 'loremipsum2']
        ];
        $mapper = JsonMapperBuilder::new()
            ->withMiddleware(new DocBlockAnnotations(new NullCache()))
            ->withMiddleware(new NamespaceResolver(new NullCache()))
            ->build();

        $mapper->mapObject($input, $object);

        self::assertInstanceOf(AnotherValueHolder::class, $object->anotherValueHolder);
        self::assertInstanceOf(ValueHolder::class, $object->valueHolder);
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testReturnsCorrectNamespaceWhenAliasProvidedForUse(): void
    {
        $object = new NamespaceAliasObject();
        $input = (object) [
            'valueHolder' => (object) ['value' => 'loremipsum1'],
            'anotherValueHolder' => (object) ['value' => 'loremipsum2']
        ];
        $mapper = JsonMapperBuilder::new()
            ->withMiddleware(new DocBlockAnnotations(new NullCache()))
            ->withMiddleware(new NamespaceResolver(new NullCache()))
            ->build();

        $mapper->mapObject($input, $object);

        self::assertInstanceOf(AnotherValueHolder::class, $object->anotherValueHolder);
        self::assertInstanceOf(ValueHolder::class, $object->valueHolder);
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testReturnsCorrectNamespaceWithPropertyDefinedInParentInOtherNamespace(): void
    {
        $object = new Customer();
        $input = (object) [
            'customerState' => (object) ['description' => 'loremipsum'],
        ];
        $mapper = JsonMapperBuilder::new()
            ->withMiddleware(new DocBlockAnnotations(new NullCache()))
            ->withMiddleware(new NamespaceResolver(new NullCache()))
            ->build();

        $mapper->mapObject($input, $object);

        self::assertInstanceOf(CustomerState::class, $object->customerState);
        self::assertEquals($input->customerState->description, $object->customerState->description);
    }
}
