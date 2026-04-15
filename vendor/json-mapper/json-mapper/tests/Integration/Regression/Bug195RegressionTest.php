<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Regression;

use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\Tests\Implementation\Php80\PopoWithConstructAndDocblock;
use JsonMapper\Tests\Implementation\Php80\Popo;
use PHPUnit\Framework\TestCase;

class Bug195RegressionTest extends TestCase
{
    /**
     * @test
     * @coversNothing
     */
    public function canHandleArrayShapeDocBlockAnnotations(): void
    {
        $factoryRegistry = new FactoryRegistry();
        $mapper = JsonMapperBuilder::new()
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->withDocBlockAnnotationsMiddleware()
            ->withTypedPropertiesMiddleware()
            ->withNamespaceResolverMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->build();

        $object = new class {
            /** @var array<string, array{files?:array<string>,classmap?:array<string>,"psr-4":array<string|array<string>>}>|array{} */
            public $overrideAutoload;
        };
        $json = json_encode(
            (object) [
                'overrideAutoload' => (object) [
                    'files' => ['file1', 'file2'],
                    'classmap' => null,
                    'psr-4' => ['one', 'two']
                ],
            ],
            JSON_THROW_ON_ERROR
        );

        $result = $mapper->mapToClassFromString($json, get_class($object));

        $object->overrideAutoload = [
            'files' => ['file1', 'file2'],
            'classmap' => null,
            'psr-4' => ['one', 'two']
        ];
        self::assertInstanceOf(get_class($object), $result);
        self::assertEquals($object, $result);
    }
}
