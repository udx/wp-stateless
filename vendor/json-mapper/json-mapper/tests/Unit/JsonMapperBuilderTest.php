<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit;

use JsonMapper\Builders\PropertyMapperBuilder;
use JsonMapper\Dto\NamedMiddleware;
use JsonMapper\Enums\TextNotation;
use JsonMapper\Exception\BuilderException;
use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\Middleware\Attributes\Attributes;
use JsonMapper\Middleware\CaseConversion;
use JsonMapper\Middleware\Constructor\Constructor;
use JsonMapper\Middleware\Debugger;
use JsonMapper\Middleware\DocBlockAnnotations;
use JsonMapper\Middleware\FinalCallback;
use JsonMapper\Middleware\NamespaceResolver;
use JsonMapper\Middleware\Rename\Mapping;
use JsonMapper\Middleware\Rename\Rename;
use JsonMapper\Middleware\TypedProperties;
use JsonMapper\Tests\Implementation\JsonMapper;
use JsonMapper\Tests\Implementation\SimpleObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class JsonMapperBuilderTest extends TestCase
{
    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testCanReturnFreshInstance(): void
    {
        $instance = JsonMapperBuilder::new();
        $otherInstance = JsonMapperBuilder::new();

        self::assertNotSame($instance, $otherInstance);
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testThrowsExceptionWhenBuildingWithoutMiddleware(): void
    {
        $this->expectException(BuilderException::class);

        JsonMapperBuilder::new()->build();
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithCustomJsonMapperClassName(): void
    {
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withDocBlockAnnotationsMiddleware()
            ->build();

        self::assertInstanceOf(JsonMapper::class, $instance);
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testThrowsExceptionSettingJsonMapperClassNameForClassWithoutProperImplementation(): void
    {
        $this->expectException(BuilderException::class);

        JsonMapperBuilder::new()->withJsonMapperClassName(\stdClass::class);
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithCustomPropertyMapper(): void
    {
        $propertyMapper = PropertyMapperBuilder::new()->build();
        /** @var JsonMapper $instance */
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withPropertyMapper($propertyMapper)
            ->withDocBlockAnnotationsMiddleware()
            ->build();

        self::assertSame($propertyMapper, $instance->handler);
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithNamespaceResolverMiddleware(): void
    {
        /** @var JsonMapper $instance */
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withNamespaceResolverMiddleware()
            ->build();

        self::assertCount(1, array_filter($instance->stack, static function (NamedMiddleware $middleware): bool {
            return $middleware->getMiddleware() instanceof NamespaceResolver;
        }));
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithDocBlockAnnotationsMiddleware(): void
    {
        /** @var JsonMapper $instance */
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withDocBlockAnnotationsMiddleware()
            ->build();

        self::assertCount(1, array_filter($instance->stack, static function (NamedMiddleware $middleware): bool {
            return $middleware->getMiddleware() instanceof DocBlockAnnotations;
        }));
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithTypedPropertiesMiddleware(): void
    {
        /** @var JsonMapper $instance */
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withTypedPropertiesMiddleware()
            ->build();

        self::assertCount(1, array_filter($instance->stack, static function (NamedMiddleware $middleware): bool {
            return $middleware->getMiddleware() instanceof TypedProperties;
        }));
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithAttributesMiddleware(): void
    {
        /** @var JsonMapper $instance */
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withAttributesMiddleware()
            ->build();

        self::assertCount(1, array_filter($instance->stack, static function (NamedMiddleware $middleware): bool {
            return $middleware->getMiddleware() instanceof Attributes;
        }));
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithRenameMiddleware(): void
    {
        /** @var JsonMapper $instance */
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withRenameMiddleware(new Mapping(SimpleObject::class, 'first_name', 'name'))
            ->build();

        self::assertCount(1, array_filter($instance->stack, static function (NamedMiddleware $middleware): bool {
            return $middleware->getMiddleware() instanceof Rename;
        }));
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithCaseConversionMiddleware(): void
    {
        /** @var JsonMapper $instance */
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withCaseConversionMiddleware(TextNotation::UNDERSCORE(), TextNotation::CAMEL_CASE())
            ->build();

        self::assertCount(1, array_filter($instance->stack, static function (NamedMiddleware $middleware): bool {
            return $middleware->getMiddleware() instanceof CaseConversion;
        }));
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithDebuggerMiddleware(): void
    {
        /** @var JsonMapper $instance */
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withDebuggerMiddleware(new NullLogger())
            ->build();

        self::assertCount(1, array_filter($instance->stack, static function (NamedMiddleware $middleware): bool {
            return $middleware->getMiddleware() instanceof Debugger;
        }));
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithFinalCallbackMiddleware(): void
    {
        /** @var JsonMapper $instance */
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withFinalCallbackMiddleware(static function () {
            })
            ->build();

        self::assertCount(1, array_filter($instance->stack, static function (NamedMiddleware $middleware): bool {
            return $middleware->getMiddleware() instanceof FinalCallback;
        }));
    }

    /** @covers \JsonMapper\JsonMapperBuilder */
    public function testItCanBuildWithObjectConstructorMiddleware(): void
    {
        $registry = new FactoryRegistry();
        /** @var JsonMapper $instance */
        $instance = JsonMapperBuilder::new()
            ->withJsonMapperClassName(JsonMapper::class)
            ->withPropertyMapper(new PropertyMapper($registry))
            ->withObjectConstructorMiddleware($registry)
            ->build();

        self::assertCount(1, array_filter($instance->stack, static function (NamedMiddleware $middleware): bool {
            return $middleware->getMiddleware() instanceof Constructor;
        }));
    }
}
