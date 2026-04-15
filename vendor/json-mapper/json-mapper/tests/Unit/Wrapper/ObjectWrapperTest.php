<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Wrapper;

use JsonMapper\JsonMapper;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class ObjectWrapperTest extends TestCase
{
    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testConstructorWithBothParametersAsNullThrowsException(): void
    {
        $this->expectException(\BadFunctionCallException::class);
        $this->expectExceptionMessage('Either object or className parameter must be provided, both are null');
        new ObjectWrapper();
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testConstructorInvalidObjectThrowsTypeException(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf(
            '%s::__construct(): Argument #1 ($object) must be of type object, string given, called in %s on line %d',
            ObjectWrapper::class,
            __FILE__,
            __LINE__ + 2
        ));
        new ObjectWrapper('');
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testConstructorInvalidClassNameThrowsException(): void
    {
        $invalidClassName = __FUNCTION__;
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Argument 2 (\$className) must be a valid class name, $invalidClassName given");
        new ObjectWrapper(null, $invalidClassName);
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testWrapsOriginalObject(): void
    {
        $object = new \stdClass();
        $wrapper = new ObjectWrapper($object);

        self::assertEquals($object, $wrapper->getObject());
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testCanFetchInstanceIfObjectNotSet(): void
    {
        $object = new \stdClass();
        $wrapper = new ObjectWrapper(null, \stdClass::class);

        self::assertInstanceOf(\stdClass::class, $wrapper->getObject());
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testWrapsOriginalClassName(): void
    {
        $wrapper = new ObjectWrapper(null, \stdClass::class);

        self::assertEquals(\stdClass::class, $wrapper->getClassName());
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testReflectedObjectIsOfWrappedObject(): void
    {
        $object = new \stdClass();
        $wrapper = new ObjectWrapper($object);
        $reflectedObject = $wrapper->getReflectedObject();

        self::assertEquals(get_class($object), $reflectedObject->getName());
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testCanGetNameOfWrappedObject(): void
    {
        $object = new \stdClass();
        $wrapper = new ObjectWrapper($object);

        self::assertEquals(\stdClass::class, $wrapper->getName());
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testSetObjectReplacesObjectAndClearsReflectedObject(): void
    {
        $originalObject = new \stdClass();
        $replacementObject = new Popo();
        $wrapper = new ObjectWrapper($originalObject);

        $wrapper->setObject($replacementObject);

        self::assertSame($replacementObject, $wrapper->getObject());
        self::assertNotSame($originalObject, $wrapper->getObject());
        self::assertEquals(get_class($replacementObject), $wrapper->getReflectedObject()->getName());
    }
}
