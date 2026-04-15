<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Regression;

use JsonMapper\Cache\NullCache;
use JsonMapper\JsonMapperInterface;
use JsonMapper\ValueObjects;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;
use JsonMapper\Middleware;
use JsonMapper\Middleware as MiddlewareUsingAnAlias;

class Bug140RegressionTest extends TestCase
{
    /**
     * @test
     * @coversNothing
     * @dataProvider classDataProvider
     * @param object $class
     */
    public function namespaceResolvingIsAbleToResolveWhenUsingPartialUseCombinedWithNestedNamespaceInPhpDoc($class): void
    {
        $json = (object) ['maps' => (object) ['source' => 'the moon']];
        $wrapper = new ObjectWrapper($class);
        $propertyMap = new ValueObjects\PropertyMap();
        $mapper = $this->createMock(JsonMapperInterface::class);
        $docBlockMiddleware = new Middleware\DocBlockAnnotations(new NullCache());
        $docBlockMiddleware->handle($json, $wrapper, $propertyMap, $mapper);

        $sut = new Middleware\NamespaceResolver(new NullCache());
        $sut->handle($json, $wrapper, $propertyMap, $mapper);

        self::assertEquals(
            [
                new ValueObjects\PropertyType(
                    Middleware\Attributes\MapFrom::class,
                    ValueObjects\ArrayInformation::notAnArray()
                )
            ],
            $propertyMap->getProperty('maps')->getPropertyTypes()
        );
    }

    public function classDataProvider()
    {
        return [
            'without alias' => [
                new class {
                    /** @var Middleware\Attributes\MapFrom */
                    public $maps;
                }
            ],
            'with alias' => [
                new class {
                    /** @var MiddlewareUsingAnAlias\Attributes\MapFrom */
                    public $maps;
                }
            ],
        ];
    }
}
