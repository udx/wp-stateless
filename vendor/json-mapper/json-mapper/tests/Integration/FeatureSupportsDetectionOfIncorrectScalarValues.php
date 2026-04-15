<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\Builders\PropertyMapperBuilder;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\Helpers\StrictScalarCaster;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\Tests\Implementation\SimpleObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsDetectionOfIncorrectScalarValues extends TestCase
{
    public function testItCanDetectAnIncorrectScalarValueFromJson(): void
    {
        $propertyMapper = PropertyMapperBuilder::new()
            ->withScalarCaster(new StrictScalarCaster())
            ->build();
        $mapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withTypedPropertiesMiddleware()
            ->withPropertyMapper($propertyMapper)
            ->build();
        $object = new SimpleObject();
        $json = (object) ['name' => 42];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Expected type string, type integer given');
        $mapper->mapObject($json, $object);
    }
}
