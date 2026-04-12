<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperBuilder;
use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\ValueTransformation;
use JsonMapper\Tests\Implementation\Popo;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsValueTransformation extends TestCase
{
    public function testItCanTransformValuesWithStringCallable(): void
    {
        // Arrange
        $mapper = JsonMapperBuilder::new()
            ->withMiddleware(new ValueTransformation('strtolower'))
            ->withDocBlockAnnotationsMiddleware()
            ->withNamespaceResolverMiddleware()
            ->build();
        $object = new Popo();
        $json = (object) ['name' => __METHOD__];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(strtolower(__METHOD__), $object->name);
    }

    public function testItCanTransformValuesWithStaticFunctionCallable(): void
    {
        // Arrange
        $now = new \DateTimeImmutable('2021-10-28T20:40:15+01:00');
        $mapper = JsonMapperBuilder::new()
            ->withMiddleware(new ValueTransformation(static function ($key, $value) {
                return $key === 'date' ? base64_decode($value) : $value;
            }, true))
            ->withDocBlockAnnotationsMiddleware()
            ->withNamespaceResolverMiddleware()
            ->build();
        $object = new Popo();
        $json = (object) ['name' => __METHOD__, 'date' => base64_encode($now->format('c'))];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(__METHOD__, $object->name);
        self::assertEquals($now, $object->date);
    }
}
