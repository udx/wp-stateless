<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware;

use JsonMapper\Cache\NullCache;
use JsonMapper\Enums\Visibility;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\TypedProperties;
use JsonMapper\Tests\Helpers\AssertThatPropertyTrait;
use JsonMapper\Tests\Helpers\CacheKeyHelper;
use JsonMapper\Tests\Implementation\Php74;
use JsonMapper\Tests\Implementation\Php80;
use JsonMapper\Tests\Implementation\SimpleObject;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class TypedPropertiesTest extends TestCase
{
    use AssertThatPropertyTrait;

    /**
     * @covers \JsonMapper\Middleware\TypedProperties
     * @requires PHP 7.4
     */
    public function testTypedPropertyIsCorrectlyDiscoveredWithPhp74(): void
    {
        $middleware = new TypedProperties(new NullCache());
        $object = new Php74\Popo();
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('name'));
        self::assertThatProperty($propertyMap->getProperty('name'))
            ->hasType('string', ArrayInformation::notAnArray())
            ->hasVisibility(Visibility::PUBLIC())
            ->isNotNullable();
        self::assertTrue($propertyMap->hasProperty('friends'));
        self::assertThatProperty($propertyMap->getProperty('friends'))
            ->hasType('mixed', ArrayInformation::singleDimension())
            ->hasVisibility(Visibility::PUBLIC())
            ->isNotNullable();
    }

    /**
     * @covers \JsonMapper\Middleware\TypedProperties
     * @requires PHP >= 8.0
     */
    public function testTypedPropertyIsCorrectlyDiscoveredWithPhp80AndGreater(): void
    {
        $middleware = new TypedProperties(new NullCache());
        $object = new Php80\Popo();
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('name'));
        self::assertThatProperty($propertyMap->getProperty('name'))
            ->hasType('string', ArrayInformation::notAnArray())
            ->hasVisibility(Visibility::PUBLIC())
            ->isNotNullable();
        self::assertTrue($propertyMap->hasProperty('mixedParam'));
        self::assertThatProperty($propertyMap->getProperty('mixedParam'))
            ->hasType('mixed', ArrayInformation::notAnArray())
            ->hasVisibility(Visibility::PUBLIC())
            ->isNullable();
    }

    /**
     * @covers \JsonMapper\Middleware\TypedProperties
     * @requires PHP >= 7.4
     */
    public function testDoesntBreakOnMissingTypeDefinition(): void
    {
        $middleware = new TypedProperties(new NullCache());
        $object = new SimpleObject();
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertCount(0, $propertyMap);
    }

    /**
     * @covers \JsonMapper\Middleware\TypedProperties
     * @requires PHP >= 7.4
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
        $middleware = new TypedProperties($cache);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), $objectWrapper, $propertyMap, $jsonMapper);
    }

    /**
     * @covers \JsonMapper\Middleware\TypedProperties
     * @requires PHP >= 8.0
     */
    public function testTypedUnionPropertyIsCorrectlyDiscovered(): void
    {
        $middleware = new TypedProperties(new NullCache());
        $object = new Php80\Popo();
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('amount'));
        $this->assertThatProperty($propertyMap->getProperty('amount'))
            ->hasType('int', ArrayInformation::notAnArray())
            ->hasType('float', ArrayInformation::notAnArray())
            ->hasVisibility(Visibility::PUBLIC())
            ->isNotNullable();
    }

    /**
     * @covers \JsonMapper\Middleware\TypedProperties
     * @requires PHP >= 8.0
     */
    public function testComplexUnionWithArrayTypedUnionPropertyIsCorrectlyDiscovered(): void
    {
        $middleware = new TypedProperties(new NullCache());
        $object = new Php80\Popo();
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('complexUnionWithArray'));
        $this->assertThatProperty($propertyMap->getProperty('complexUnionWithArray'))
            ->onlyHasType('mixed', ArrayInformation::singleDimension())
            ->hasVisibility(Visibility::PUBLIC())
            ->isNotNullable();
    }
}
