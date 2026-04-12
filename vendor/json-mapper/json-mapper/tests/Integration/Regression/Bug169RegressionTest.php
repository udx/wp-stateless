<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Regression;

use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\Tests\Implementation\Php80\PopoWithConstructAndDocblock;
use JsonMapper\Tests\Implementation\Php80\Popo;
use PHPUnit\Framework\TestCase;

class Bug169RegressionTest extends TestCase
{
    /**
     * @test
     * @coversNothing
     * @requires PHP >= 8.0
     */
    public function canHandleVarNotationOnPublicProperty(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $mapper = JsonMapperBuilder::new()
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->withDocBlockAnnotationsMiddleware()
            ->withTypedPropertiesMiddleware()
            ->withNamespaceResolverMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->build();

        $json = json_encode((object)['popo' => [
            (object) ['name' => 'John Doe'],
        ]]);

        $result = $mapper->mapToClassFromString($json, PopoWithConstructAndDocblock::class);

        self::assertInstanceOf(PopoWithConstructAndDocblock::class, $result);
        self::assertArrayHasKey(0, $result->popo);
        self::assertInstanceOf(Popo::class, $result->popo[0]);
        self::assertSame('John Doe', $result->popo[0]->name);
    }
}
