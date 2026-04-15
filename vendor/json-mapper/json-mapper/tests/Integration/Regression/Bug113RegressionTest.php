<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Regression;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\Php81\Status;
use PHPUnit\Framework\TestCase;

class Bug113RegressionTest extends TestCase
{
    /**
     * @test
     * @coversNothing
     */
    public function jsonMapperCanHandleMultidimensionalArraysWithScalarType(): void
    {
        $mapper = (new JsonMapperFactory())->bestFit();

        $object = new class {
            /** @var string[][] */
            public $value;
        };
        $json = (object) [
            'value' => [
                'a' => [
                    'key-a' => 'value-a'
                ],
                'b' => [
                    'key-b' => 'value-b'
                ]
            ]
        ];

        $result = $mapper->mapObject($json, $object);

        self::assertArrayHasKey('a', $result->value);
        self::assertArrayHasKey('key-a', $result->value['a']);
        self::assertEquals('value-a', $result->value['a']['key-a']);
        self::assertArrayHasKey('b', $result->value);
        self::assertArrayHasKey('key-b', $result->value['b']);
        self::assertEquals('value-b', $result->value['b']['key-b']);
    }

    /**
     * @test
     * @coversNothing
     * @requires PHP >= 8.1
     */
    public function jsonMapperCanHandleMultidimensionalArraysWithEnumType(): void
    {
        $mapper = (new JsonMapperFactory())->bestFit();

        $object = new class {
            /** @var Status[][] */
            public $value;
        };
        $json = (object) [
            'value' => [
                'a' => [
                    'status-a' => 'draft'
                ],
                'b' => [
                    'status-b' => 'published'
                ]
            ]
        ];

        $result = $mapper->mapObject($json, $object);

        self::assertArrayHasKey('a', $result->value);
        self::assertArrayHasKey('status-a', $result->value['a']);
        self::assertEquals(Status::DRAFT, $result->value['a']['status-a']);
        self::assertArrayHasKey('b', $result->value);
        self::assertArrayHasKey('status-b', $result->value['b']);
        self::assertEquals(Status::PUBLISHED, $result->value['b']['status-b']);
    }
}
