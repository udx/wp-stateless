<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Regression;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\Foo\Test;
use PHPUnit\Framework\TestCase;

class Bug116RegressionTest extends TestCase
{
    /**
     * @test
     * @requires PHP >= 7.4
     * @coversNothing
     */
    public function jsonMapperDoesImportLookupForParentClasses(): void
    {
        $mapper = (new JsonMapperFactory())->bestFit();
        $expected = 'some-type-string';
        $data = (object) ['meta' => (object) ['type' => $expected]];
        $object = new Test();

        $mapper->mapObject($data, $object);

        self::assertSame($expected, $object->meta->type);
    }
}
