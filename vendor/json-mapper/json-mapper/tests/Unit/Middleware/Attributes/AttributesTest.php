<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware\Attributes;

use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\Attributes\Attributes;
use JsonMapper\Tests\Implementation\Php80\AttributePopo;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\Attributes\Attributes
     * @requires PHP >= 8.0
     */
    public function testAttributesMiddlewareDoesMapFrom(): void
    {
        $json = (object) ['Identifier' => 42, 'UserName' => 'John Doe'];
        $object = new AttributePopo();
        $propertyMap = new PropertyMap();
        $middleware = new Attributes();
        $mapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $mapper);

        self::assertEquals((object) ['id' => 42, 'name' => 'John Doe'], $json);
    }

    /**
     * @covers \JsonMapper\Middleware\Attributes\Attributes
     * @requires PHP >= 8.0
     */
    public function testAttributesMiddlewareWithPartialDataDoesMapFrom(): void
    {
        $json = (object) ['Identifier' => 42];
        $object = new AttributePopo();
        $propertyMap = new PropertyMap();
        $middleware = new Attributes();
        $mapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $mapper);

        self::assertEquals((object) ['id' => 42], $json);
    }

    /**
     * @covers \JsonMapper\Middleware\Attributes\Attributes
     * @requires PHP >= 8.0
     */
    public function testAttributesMiddlewareWhenSourceAndTargetAreEqualDoesntRemoveSource(): void
    {
        $json = (object) ['email' => 'JohnDoe@example.org'];
        $object = new AttributePopo();
        $propertyMap = new PropertyMap();
        $middleware = new Attributes();
        $mapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle($json, new ObjectWrapper($object), $propertyMap, $mapper);

        self::assertEquals((object) ['email' => 'JohnDoe@example.org'], $json);
    }
}
