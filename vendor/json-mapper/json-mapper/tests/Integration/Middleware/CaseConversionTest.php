<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Middleware;

use JsonMapper\Enums\TextNotation;
use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\CaseConversion;
use JsonMapper\Tests\Implementation\ComplexObject;
use PHPUnit\Framework\TestCase;

class CaseConversionTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\CaseConversion
     */
    public function testCaseConversionMiddlewareDoesCaseConversion(): void
    {
        $mapper = (new JsonMapperFactory())->default();
        $mapper->push(new CaseConversion(TextNotation::STUDLY_CAPS(), TextNotation::CAMEL_CASE()));
        $object = new ComplexObject();
        $json = (object) ['User' => (object) ['Name' => __METHOD__]];

        $mapper->mapObject($json, $object);

        self::assertEquals(__METHOD__, $object->getUser()->getName());
    }
}
