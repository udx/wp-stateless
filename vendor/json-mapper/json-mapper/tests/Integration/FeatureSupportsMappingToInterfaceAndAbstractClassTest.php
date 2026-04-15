<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\Builders\PropertyMapperBuilder;
use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\Tests\Implementation\Models\AbstractShape;
use JsonMapper\Tests\Implementation\Models\IShape;
use JsonMapper\Tests\Implementation\Models\ShapeInstanceFactory;
use JsonMapper\Tests\Implementation\Models\Square;
use JsonMapper\Tests\Implementation\Models\Wrappers\AbstractShapeWrapper;
use JsonMapper\Tests\Implementation\Models\Wrappers\IShapeAware;
use JsonMapper\Tests\Implementation\Models\Wrappers\IShapeWrapper;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsMappingToInterfaceAndAbstractClassTest extends TestCase
{
    /**
     * @dataProvider nonInstantiableTypes
     */
    public function testItCanMapToAnInterfaceType(IShapeAware $object, string $className): void
    {
        $interfaceResolver = new FactoryRegistry();
        $interfaceResolver->addFactory($className, new ShapeInstanceFactory());
        $propertyMapper = PropertyMapperBuilder::new()
            ->withNonInstantiableTypeResolver($interfaceResolver)
            ->build();
        $mapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withNamespaceResolverMiddleware()
            ->withPropertyMapper($propertyMapper)
            ->build();

        $mapper->mapObjectFromString('{"shape": {"type": "square", "width": 5, "length": 6}}', $object);

        self::assertInstanceOf(Square::class, $object->shape);
        self::assertEquals(5, $object->shape->width);
        self::assertEquals(6, $object->shape->length);
    }

    public function nonInstantiableTypes(): array
    {
        return [
            'interface' => [new IShapeWrapper(), IShape::class],
            'abstract' => [new AbstractShapeWrapper(), AbstractShape::class]
        ];
    }
}
