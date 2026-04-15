<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Regression;

use JsonMapper\JsonMapperFactory;
use PHPUnit\Framework\TestCase;

class Bug120RegressionTest extends TestCase
{
    /**
     * @test
     * @coversNothing
     */
    public function jsonMapperEagerlyMapsToSingleScalarValueForUnionTypeWhereValueIsArray(): void
    {
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var string|string[] */
            public $value;
        };
        $json = (object) [
            'value' => []
        ];

        $mapper->mapObject($json, $object);

        self::assertEquals($json->value, $object->value);
    }
}
