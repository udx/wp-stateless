<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware\Constructor;

use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Helpers\ScalarCaster;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\Constructor\DefaultFactory;
use JsonMapper\Tests\Implementation\Php81\BlogPostWithConstructor;
use JsonMapper\Tests\Implementation\Php81\Status;
use JsonMapper\Tests\Implementation\Php81\WithConstructorReadOnlyDateTimePropertyPromotion;
use JsonMapper\Tests\Implementation\Popo;
use PHPUnit\Framework\TestCase;
use stdClass;

class DefaultFactoryTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithoutParameters(): void
    {
        $class = new class {
            public function __construct()
            {
            }
        };
        $sut = new DefaultFactory(
            get_class($class),
            (new \ReflectionClass($class))->getConstructor(),
            $this->createMock(JsonMapperInterface::class),
            new ScalarCaster(),
            new FactoryRegistry()
        );

        $result = $sut->__invoke(new \stdClass());

        self::assertInstanceOf(get_class($class), $result);
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithSingleParameterWithoutDocBlock(): void
    {
        $value = random_int(0, PHP_INT_MAX);
        $class = new class {
            /** @var int */
            private $value;

            public function __construct(int $value = 0)
            {
                $this->value = $value;
            }

            public function getValue(): int
            {
                return $this->value;
            }
        };
        $sut = new DefaultFactory(
            get_class($class),
            (new \ReflectionClass($class))->getConstructor(),
            $this->createMock(JsonMapperInterface::class),
            new ScalarCaster(),
            new FactoryRegistry()
        );

        $result = $sut->__invoke((object) ['value' => $value]);

        self::assertInstanceOf(get_class($class), $result);
        self::assertEquals($value, $result->getValue());
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithTwoParametersWithoutDocBlock(): void
    {
        $first = random_int(0, PHP_INT_MAX);
        $second = random_int(0, PHP_INT_MAX);
        $class = new class {
            /** @var int */
            private $first;
            /** @var int */
            private $second;

            public function __construct(int $first = 0, int $second = 0)
            {
                $this->first = $first;
                $this->second = $second;
            }

            public function getFirst(): int
            {
                return $this->first;
            }

            public function getSecond(): int
            {
                return $this->second;
            }
        };
        $sut = new DefaultFactory(
            get_class($class),
            (new \ReflectionClass($class))->getConstructor(),
            $this->createMock(JsonMapperInterface::class),
            new ScalarCaster(),
            new FactoryRegistry()
        );

        $result = $sut->__invoke((object) ['second' => $second, 'first' => $first]);

        self::assertInstanceOf(get_class($class), $result);
        self::assertEquals($first, $result->getFirst());
        self::assertEquals($second, $result->getSecond());
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithTwoParametersHintedThroughDocBlock(): void
    {
        $first = random_int(0, PHP_INT_MAX);
        $second = random_int(0, PHP_INT_MAX);
        $class = new class {
            /** @var int */
            private $first;
            /** @var int */
            private $second;

            /**
             * @param int $first
             * @param int $second
             */
            public function __construct($first = 0, $second = 0)
            {
                $this->first = $first;
                $this->second = $second;
            }

            public function getFirst(): int
            {
                return $this->first;
            }

            public function getSecond(): int
            {
                return $this->second;
            }
        };
        $sut = new DefaultFactory(
            get_class($class),
            (new \ReflectionClass($class))->getConstructor(),
            $this->createMock(JsonMapperInterface::class),
            new ScalarCaster(),
            new FactoryRegistry()
        );

        $result = $sut->__invoke((object) ['second' => $second, 'first' => $first]);

        self::assertInstanceOf(get_class($class), $result);
        self::assertEquals($first, $result->getFirst());
        self::assertEquals($second, $result->getSecond());
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithOneArrayParameterHintedThroughDocBlock(): void
    {
        $first = [random_int(0, PHP_INT_MAX), random_int(0, PHP_INT_MAX)];
        $class = new class {
            /** @var int[] */
            private $first;

            /**
             * @param int[] $first
             */
            public function __construct($first = [])
            {
                $this->first = $first;
            }

            public function getFirst(): array
            {
                return $this->first;
            }
        };
        $sut = new DefaultFactory(
            get_class($class),
            (new \ReflectionClass($class))->getConstructor(),
            $this->createMock(JsonMapperInterface::class),
            new ScalarCaster(),
            new FactoryRegistry()
        );

        $result = $sut->__invoke((object) ['first' => $first]);

        self::assertInstanceOf(get_class($class), $result);
        self::assertEquals($first, $result->getFirst());
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithOneObjectParameter(): void
    {
        $name = 'Jane Doe';
        $class = new class {
            /** @var ?Popo */
            private $value;

            public function __construct(?Popo $value = null)
            {
                $this->value = $value;
            }

            public function getValue(): ?Popo
            {
                return $this->value;
            }
        };
        $mapper = $this->createMock(JsonMapperInterface::class);
        $mapper->method('mapToClass')
            ->with($this->isInstanceOf(\stdClass::class), Popo::class)
            ->willReturnCallback(function (stdClass $data) {
                $popo = new Popo();
                $popo->name = isset($data->name) ? $data->name : null;
                $popo->date = isset($data->date) ? $data->date : null;
                $popo->notes = isset($data->notes) ? $data->notes : null;

                return $popo;
            });
        $sut = new DefaultFactory(
            get_class($class),
            (new \ReflectionClass($class))->getConstructor(),
            $mapper,
            new ScalarCaster(),
            new FactoryRegistry()
        );

        $result = $sut->__invoke((object) ['value' => (object) ['name' => $name]]);

        self::assertInstanceOf(get_class($class), $result);
        self::assertInstanceOf(Popo::class, $result->getValue());
        self::assertEquals($name, $result->getValue()->name);
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithArrayOfObjectParameter(): void
    {
        $name = 'Jane Doe';
        $class = new class {
            /** @var Popo[] */
            private $value;

            /** @param Popo[] $value */
            public function __construct(array $value = [])
            {
                $this->value = $value;
            }

            /** @return array<int, Popo> */
            public function getValue(): array
            {
                return $this->value;
            }
        };
        $mapper = $this->createMock(JsonMapperInterface::class);
        $mapper->method('mapToClass')
            ->with($this->isInstanceOf(\stdClass::class), Popo::class)
            ->willReturnCallback(function (stdClass $data) {
                $popo = new Popo();
                $popo->name = $data->name ?? null;
                $popo->date = $data->date ?? null;
                $popo->notes = $data->notes ?? null;

                return $popo;
            });
        $sut = new DefaultFactory(
            get_class($class),
            (new \ReflectionClass($class))->getConstructor(),
            $mapper,
            new ScalarCaster(),
            new FactoryRegistry()
        );

        $result = $sut->__invoke(
            (object) [
                'value' => [(object) ['name' => $name], (object) ['name' => strrev($name)]]
            ]
        );

        self::assertInstanceOf(get_class($class), $result);
        self::assertContainsOnlyInstancesOf(Popo::class, $result->getValue());
        self::assertEquals($name, $result->getValue()[0]->name);
        self::assertEquals(strrev($name), $result->getValue()[1]->name);
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     * @requires PHP >= 8.1
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithEnumParameter(): void
    {
        $name = 'Jane Doe';
        $mapper = $this->createMock(JsonMapperInterface::class);

        $sut = new DefaultFactory(
            BlogPostWithConstructor::class,
            (new \ReflectionClass(BlogPostWithConstructor::class))->getConstructor(),
            $mapper,
            new ScalarCaster(),
            new FactoryRegistry()
        );

        $result = $sut->__invoke(
            (object) [
                'status' => 'draft'
            ]
        );

        self::assertInstanceOf(BlogPostWithConstructor::class, $result);
        self::assertEquals(Status::DRAFT, $result->getStatus());
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     * @requires PHP >= 8.1
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithNativeTypeParameter(): void
    {
        $date = new \DateTimeImmutable("today");
        $mapper = $this->createMock(JsonMapperInterface::class);

        $sut = new DefaultFactory(
            WithConstructorReadOnlyDateTimePropertyPromotion::class,
            (new \ReflectionClass(WithConstructorReadOnlyDateTimePropertyPromotion::class))->getConstructor(),
            $mapper,
            new ScalarCaster(),
            FactoryRegistry::withNativePhpClassesAdded()
        );

        $result = $sut->__invoke(
            (object) [
                'date' => $date->format('Y-m-d H:i:s'),
            ]
        );

        self::assertInstanceOf(WithConstructorReadOnlyDateTimePropertyPromotion::class, $result);
        self::assertEquals($date, $result->date);
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithArrayLtGtOfObjectParameter(): void
    {
        $name = 'Jane Doe';
        $class = new class {
            /** @var array<int, Popo> */
            private $value;

            /** @param array<int, Popo> $value */
            public function __construct(array $value = [])
            {
                $this->value = $value;
            }

            /** @return array<int, Popo> */
            public function getValue(): array
            {
                return $this->value;
            }
        };
        $mapper = $this->createMock(JsonMapperInterface::class);
        $mapper->method('mapToClass')
            ->with($this->isInstanceOf(\stdClass::class), Popo::class)
            ->willReturnCallback(function (stdClass $data) {
                $popo = new Popo();
                $popo->name = $data->name ?? null;
                $popo->date = $data->date ?? null;
                $popo->notes = $data->notes ?? null;

                return $popo;
            });
        $sut = new DefaultFactory(
            get_class($class),
            (new \ReflectionClass($class))->getConstructor(),
            $mapper,
            new ScalarCaster(),
            new FactoryRegistry()
        );

        $result = $sut->__invoke(
            (object) [
                'value' => [(object) ['name' => $name], (object) ['name' => strrev($name)]]
            ]
        );

        self::assertInstanceOf(get_class($class), $result);
        self::assertContainsOnlyInstancesOf(Popo::class, $result->getValue());
        self::assertEquals($name, $result->getValue()[0]->name);
        self::assertEquals(strrev($name), $result->getValue()[1]->name);
    }

    /**
     * @covers \JsonMapper\Middleware\Constructor\DefaultFactory
     */
    public function testDefaultFactoryCanHandleObjectWithConstructorWithListOfObjectParameter(): void
    {
        $name = 'Jane Doe';
        $class = new class {
            /** @var list<Popo> */
            private $value;

            /** @param list<Popo> $value */
            public function __construct(array $value = [])
            {
                $this->value = $value;
            }

            /** @return list<Popo> */
            public function getValue(): array
            {
                return $this->value;
            }
        };
        $mapper = $this->createMock(JsonMapperInterface::class);
        $mapper->method('mapToClass')
            ->with($this->isInstanceOf(\stdClass::class), Popo::class)
            ->willReturnCallback(function (stdClass $data) {
                $popo = new Popo();
                $popo->name = $data->name ?? null;
                $popo->date = $data->date ?? null;
                $popo->notes = $data->notes ?? null;

                return $popo;
            });
        $sut = new DefaultFactory(
            get_class($class),
            (new \ReflectionClass($class))->getConstructor(),
            $mapper,
            new ScalarCaster(),
            new FactoryRegistry()
        );

        $result = $sut->__invoke(
            (object) [
                'value' => [(object) ['name' => $name], (object) ['name' => strrev($name)]]
            ]
        );

        self::assertInstanceOf(get_class($class), $result);
        self::assertContainsOnlyInstancesOf(Popo::class, $result->getValue());
        self::assertEquals($name, $result->getValue()[0]->name);
        self::assertEquals(strrev($name), $result->getValue()[1]->name);
    }
}
