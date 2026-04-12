<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Regression;

use JsonMapper\Cache\NullCache;
use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Tests\Implementation\DatePopoWithConstructor;
use JsonMapper\ValueObjects;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;
use JsonMapper\Middleware;
use JsonMapper\Middleware as MiddlewareUsingAnAlias;

class Bug146RegressionTest extends TestCase
{
    /**
     * @test
     * @coversNothing
     */
    public function canHandleCustomConstructorWithNullableDateTime(): void
    {
        $factoryRegistry = FactoryRegistry::withNativePhpClassesAdded();
        $mapper = JsonMapperBuilder::new()
            ->withDocBlockAnnotationsMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->build();

        $json = (object) [
            'date' => null,
        ];

        $result = $mapper->mapToClass($json, DatePopoWithConstructor::class);

        self::assertNull($result->getDate());
    }
}
