<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware\Rename;

use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\Rename\Rename;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\Models\User;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class RenameTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\Rename\Rename
     */
    public function testLeavesJsonUntouchedWithEmptyMapping(): void
    {
        $middleware = new Rename();
        $json = (object) ['name' => 'John Doe', 'id' => 42];
        $clone = clone $json;
        $wrapper = new ObjectWrapper(new User());
        $propertyMap = new PropertyMap();
        $mapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle($clone, $wrapper, $propertyMap, $mapper);

        self::assertEquals($json, $clone);
    }

    /**
     * @covers \JsonMapper\Middleware\Rename\Rename
     */
    public function testLeavesJsonUntouchedWithPropertyNotInMapping(): void
    {
        $middleware = new Rename();
        $middleware->addMapping(User::class, 'municipality', 'city');
        $json = (object) ['name' => 'John Doe', 'id' => 42];
        $clone = clone $json;
        $wrapper = new ObjectWrapper(new User());
        $propertyMap = new PropertyMap();
        $mapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle($clone, $wrapper, $propertyMap, $mapper);

        self::assertEquals($json, $clone);
    }

    /**
     * @covers \JsonMapper\Middleware\Rename\Rename
     */
    public function testLeavesJsonUntouchedWithPropertyInMappingForDifferentClass(): void
    {
        $middleware = new Rename();
        $middleware->addMapping(ComplexObject::class, 'name', 'fullName');
        $json = (object) ['name' => 'John Doe', 'id' => 42];
        $clone = clone $json;
        $wrapper = new ObjectWrapper(new User());
        $propertyMap = new PropertyMap();
        $mapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle($clone, $wrapper, $propertyMap, $mapper);

        self::assertEquals($json, $clone);
    }

    /**
     * @covers \JsonMapper\Middleware\Rename\Rename
     */
    public function testAltersJsonWithPropertyInMapping(): void
    {
        $middleware = new Rename();
        $middleware->addMapping(User::class, 'name', 'fullName');
        $json = (object) ['name' => 'John Doe', 'id' => 42];
        $clone = clone $json;
        $wrapper = new ObjectWrapper(new User());
        $propertyMap = new PropertyMap();
        $mapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle($clone, $wrapper, $propertyMap, $mapper);

        self::assertEquals($json->id, $clone->id);
        self::assertEquals($json->name, $clone->fullName);
    }
}
