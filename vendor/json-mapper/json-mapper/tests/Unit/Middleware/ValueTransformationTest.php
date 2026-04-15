<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware;

use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\ValueTransformation;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValueTransformationTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\ValueTransformation
     */
    public function testWithFunctionAsString(): void
    {
        $sut = new ValueTransformation('strtoupper');

        $sut->handle(
            $json = (object) [
                'name' => 'small',
            ],
            new ObjectWrapper(new Popo()),
            new PropertyMap(),
            $this->createMock(JsonMapperInterface::class)
        );

        self::assertEquals('SMALL', $json->name);
    }

    /**
     * @covers \JsonMapper\Middleware\ValueTransformation
     * @dataProvider valueMapperDataProvider
     */
    public function testCanConvertObject(
        ValueTransformation $middleware,
        stdClass $json,
        stdClass $expected
    ): void {
        $middleware->handle(
            $json,
            new ObjectWrapper(new Popo()),
            new PropertyMap(),
            $this->createMock(JsonMapperInterface::class)
        );

        self::assertEquals($expected, $json);
    }

    public function valueMapperDataProvider(): array
    {
        return [
            'php function strtoupper' => [
                new ValueTransformation('strtoupper'),
                (object) [
                    'name' => 'test',
                    'notes' => 'this is a test'
                ],
                (object) [
                    'name' => 'TEST',
                    'notes' => 'THIS IS A TEST'
                ]
            ],
            'custom function' => [
                new ValueTransformation(
                    static function ($key, $value) {
                        if ($key === 'notes') {
                            return \base64_decode($value);
                        }

                        return $value;
                    },
                    true
                ),
                (object) [
                    'name' => 'test',
                    'notes' => 'c3RyaW5nIGluIGJhc2U2NA=='
                ],
                (object) [
                    'name' => 'test',
                    'notes' => 'string in base64'
                ]
            ]
        ];
    }
}
