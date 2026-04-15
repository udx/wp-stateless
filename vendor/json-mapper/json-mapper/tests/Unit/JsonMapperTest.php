<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit;

use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapper;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\AbstractMiddleware;
use JsonMapper\Tests\Implementation\IsCalledHandler;
use JsonMapper\Tests\Implementation\IsCalledMiddleware;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class JsonMapperTest extends TestCase
{
    /** @var IsCalledHandler */
    private $handler;
    /** @var IsCalledMiddleware */
    private $middleware;

    protected function setUp(): void
    {
        $this->handler = new IsCalledHandler();
        $this->middleware = new IsCalledMiddleware();
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testHandlerFromConstructorIsInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testHandlerFromSetterIsInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper();
        $jsonMapper->setPropertyMapper($this->handler);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testHandlerFromConstructorIsInvokedWhenMappingArray(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapArray([new \stdClass()], new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testHandlerFromSetterIsInvokedWhenMappingArray(): void
    {
        $jsonMapper = new JsonMapper();
        $jsonMapper->setPropertyMapper($this->handler);

        $jsonMapper->mapArray([new \stdClass()], new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testWithoutHandlerThrowsExceptionWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(\RuntimeException::class);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testPushedMiddlewareIsInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->push($this->middleware);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertTrue($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testPushedMiddlewareIsInvokedWhenMappingArray(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->push($this->middleware);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertTrue($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testRemovedMiddlewareIsNotInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->push($this->middleware);
        $jsonMapper->remove($this->middleware);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertFalse($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testRemovedByNameMiddlewareIsNotInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->push($this->middleware, __METHOD__);
        $jsonMapper->removeByName(__METHOD__);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertFalse($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testShiftedMiddlewareIsNotInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->unshift($this->middleware, __METHOD__);
        $jsonMapper->shift();

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertFalse($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testPoppedMiddlewareIsNotInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->push($this->middleware, __METHOD__);
        $jsonMapper->pop();

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertFalse($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapObjectFromStringWithInvalidJsonThrowsException(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(\JsonException::class);
        $jsonMapper->mapObjectFromString('abcdef...', new \stdClass());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapObjectFromStringWithJsonArrayThrowsException(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Provided string is not a json encoded object');
        $jsonMapper->mapObjectFromString('[1,2,3]', new \stdClass());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapObjectFromStringWithJsonObjectCallsHandler(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapObjectFromString('{}', new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapArrayFromStringWithInvalidJsonThrowsException(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(\JsonException::class);
        $jsonMapper->mapArrayFromString('abcdef...', new \stdClass());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapArrayFromStringWithJsonObjectThrowsException(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Provided string is not a json encoded array');
        $jsonMapper->mapArrayFromString('{"one": 1}', new \stdClass());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapArrayFromStringWithJsonArrayCallsHandler(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapArrayFromString('[{"one": 1}]', new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapObjectWithInvalidObjectThrowsTypeException(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf(
            '%s::mapObject(): Argument #2 ($object) must be of type object, string given, called in %s on line %d',
            get_class($jsonMapper),
            __FILE__,
            __LINE__ + 2
        ));
        $jsonMapper->mapObject(new \stdClass(), '');
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapObjectFromStringWithInvalidObjectThrowsTypeException(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf(
            '%s::mapObjectFromString(): Argument #2 ($object) must be of type object, string given, called in %s on line %d',
            get_class($jsonMapper),
            __FILE__,
            __LINE__ + 2
        ));
        $jsonMapper->mapObjectFromString('', '');
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapArrayWithInvalidObjectThrowsTypeException(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf(
            '%s::mapArray(): Argument #2 ($object) must be of type object, string given, called in %s on line %d',
            get_class($jsonMapper),
            __FILE__,
            __LINE__ + 2
        ));
        $jsonMapper->mapArray([], '');
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapArrayFromStringWithInvalidObjectThrowsTypeException(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf(
            '%s::mapArrayFromString(): Argument #2 ($object) must be of type object, string given, called in %s on line %d',
            get_class($jsonMapper),
            __FILE__,
            __LINE__ + 2
        ));
        $jsonMapper->mapArrayFromString('', '');
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassThrowsExceptionOnNonExistingClass(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\TypeError::class);
        $jsonMapper->mapToClass(new \stdClass(), 'NonExistingClass');
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassCallsHandler(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapToClass((object) [], \stdClass::class);

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassArrayThrowsExceptionOnNonExistingClass(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\TypeError::class);
        $jsonMapper->mapToClassArray([], 'NonExistingClass');
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassArrayCallsHandler(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapToClassArray([(object) []], \stdClass::class);

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassFromStringThrowsExceptionOnNonExistingClass(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\TypeError::class);
        $jsonMapper->mapToClassFromString('', 'NonExistingClass');
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassFromStringWithInvalidJsonThrowsException(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\JsonException::class);
        $jsonMapper->mapToClassFromString('{]', \stdClass::class);
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassFromStringWithJsonArrayThrowsException(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\RuntimeException::class);
        $jsonMapper->mapToClassFromString('[]', \stdClass::class);
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassFromStringCallsHandler(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapToClassFromString('{}', \stdClass::class);

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassArrayFromStringThrowsExceptionOnNonExistingClass(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\TypeError::class);
        $jsonMapper->mapToClassArrayFromString('', 'NonExistingClass');
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassArrayFromStringWithInvalidJsonThrowsException(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\JsonException::class);
        $jsonMapper->mapToClassArrayFromString('{]', \stdClass::class);
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassArrayFromStringWithJsonObjectThrowsException(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $this->expectException(\RuntimeException::class);
        $jsonMapper->mapToClassArrayFromString('{}', \stdClass::class);
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapToClassArrayFromStringCallsHandler(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapToClassArrayFromString('[{}]', \stdClass::class);

        self::assertTrue($this->handler->isCalled());
    }
}
